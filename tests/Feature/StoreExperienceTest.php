<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreExperienceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function data(Store $store, array $overrides = []): array
    {
        return array_replace([
            'name' => $store->name, 'slug' => $store->slug, 'is_listed' => false,
            'province' => $store->province, 'city' => $store->city, 'district' => $store->district,
        ], $overrides);
    }

    private function bank(array $overrides = []): array
    {
        return array_replace(['id' => null, 'bank_name' => 'Test Bank', 'account_holder' => 'Store Owner', 'account_number' => '0012 345-678', 'instructions' => null, 'is_active' => true], $overrides);
    }

    private function regionFakes(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.emsifa.com/api-wilayah-indonesia/v2/provinces.json' => Http::response(['data' => [['id' => '61', 'name' => 'Kalimantan Barat', 'lat' => -0.1, 'lng' => 110.1]]]),
            'https://www.emsifa.com/api-wilayah-indonesia/v2/regencies/61.json' => Http::response(['data' => [['id' => '61.72', 'name' => 'Kota Singkawang', 'lat' => 0.9, 'lng' => 109]]]),
            'https://www.emsifa.com/api-wilayah-indonesia/v2/districts/61.72.json' => Http::response(['data' => [['id' => '61.72.01', 'name' => 'Singkawang Barat', 'lat' => 0.9, 'lng' => 109]]]),
        ]);
    }

    public function test_owner_saves_default_delivery_choices_for_the_new_session_form(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, ['priority_enabled' => true, 'priority_fee' => 5000, 'priority_minutes' => 30, 'hemat_enabled' => true, 'hemat_fee' => 1500, 'hemat_minutes' => 60]))->assertSessionHasNoErrors();
        $this->assertSame(5000, $store->fresh()->delivery_options['priority']['fee']);
        $this->assertSame(1500, $store->fresh()->delivery_options['hemat']['fee']);
        $this->get(route('admin.open-orders.create', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('methods.delivery_options.priority.fee', 5000));
    }

    public function test_region_endpoint_requires_login(): void
    {
        $url = route('regions', ['level' => 'provinces']);
        $this->get($url)->assertRedirectToRoute('login');
        $this->regionFakes();
        $this->actingAs(User::factory()->create())->getJson($url)->assertOk();
    }

    public function test_region_lists_are_normalized_cached_and_validate_parent_paths(): void
    {
        $this->regionFakes();
        $this->actingAs(User::factory()->create());
        $url = route('regions', ['level' => 'provinces']);

        $this->getJson($url)->assertOk()->assertJsonPath('data.0.id', '61')->assertJsonPath('data.0.name', 'Kalimantan Barat');
        $this->getJson($url)->assertOk();
        Http::assertSentCount(1);
        $this->getJson(route('regions', ['level' => 'cities', 'parent' => '61']))->assertOk()->assertJsonPath('data.0.id', '61.72');
        $this->getJson(route('regions', ['level' => 'districts', 'parent' => '61.72']))->assertOk()->assertJsonPath('data.0.id', '61.72.01');
        $this->getJson(route('regions', ['level' => 'cities', 'parent' => '../../etc']))->assertUnprocessable();
        $this->getJson(route('regions', ['level' => 'districts', 'parent' => '61']))->assertUnprocessable();
        Http::assertSentCount(3);
    }

    public function test_provider_failure_returns_retryable_error_without_invented_region_data(): void
    {
        Http::fake(['https://www.emsifa.com/api-wilayah-indonesia/v2/provinces.json' => Http::response([], 503)]);
        $this->actingAs(User::factory()->create())->getJson(route('regions', ['level' => 'provinces']))
            ->assertStatus(503)->assertJsonMissingPath('data');
    }

    public function test_selected_region_codes_resolve_canonical_names_on_server(): void
    {
        $this->regionFakes();
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'province_code' => '61', 'city_code' => '61.72', 'district_code' => '61.72.01',
            'province' => 'Forged province', 'city' => 'Forged city', 'district' => 'Forged district',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'province' => 'Kalimantan Barat', 'city' => 'Kota Singkawang', 'district' => 'Singkawang Barat', 'city_code' => '61.72']);
    }

    public function test_child_region_from_another_parent_is_rejected(): void
    {
        $this->regionFakes();
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'province_code' => '61', 'city_code' => '11.01', 'district_code' => '11.01.01',
        ]))->assertSessionHasErrors('city_code');
        $this->assertNull($store->fresh()->province_code);
    }

    public function test_unchanged_saved_region_can_be_saved_without_external_request(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $store = Store::factory()->create(['province_code' => '61', 'city_code' => '61.72', 'district_code' => '61.72.01']);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'province_code' => '61', 'city_code' => '61.72', 'district_code' => '61.72.01', 'city' => 'Forged city',
        ]))->assertSessionHasNoErrors();
        $this->assertSame($store->city, $store->fresh()->city);
        Http::assertNothingSent();
    }

    public function test_region_failure_prevents_partial_store_or_bank_updates(): void
    {
        Http::fake(['https://www.emsifa.com/api-wilayah-indonesia/v2/provinces.json' => Http::response([], 503)]);
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'name' => 'Should not save', 'province_code' => '61', 'city_code' => '61.72', 'district_code' => '61.72.01', 'bank_accounts' => [$this->bank()],
        ]))->assertSessionHasErrors('province_code');
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'name' => $store->name]);
        $this->assertDatabaseCount('rintis_bank_accounts', 0);
    }

    public function test_owner_can_add_edit_and_disable_bank_accounts_while_preserving_leading_zeros(): void
    {
        $store = Store::factory()->create();
        $url = route('admin.stores.update', $store);
        $this->actingAs($store->owner)->patch($url, $this->data($store, ['bank_accounts' => [$this->bank(), $this->bank(['bank_name' => 'Other Bank', 'account_number' => '0099887766'])]]))->assertSessionHasNoErrors();
        $bank = $store->bankAccounts()->orderBy('id')->firstOrFail();
        $this->assertSame('0012345678', $bank->account_number);
        $this->assertDatabaseCount('rintis_bank_accounts', 2);
        $this->patch($url, $this->data($store, ['bank_accounts' => [$this->bank(['id' => $bank->id, 'is_active' => false, 'instructions' => 'Use your order number.'])]]))->assertSessionHasNoErrors();
        $this->assertFalse($bank->fresh()->is_active);
        $this->assertNull($bank->fresh()->instructions);
        $this->assertDatabaseCount('rintis_bank_accounts', 2);
        $this->get(route('admin.stores.edit', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->has('bankAccounts', 2)->where('bankAccounts.0.account_number', '0012345678'));
    }

    public function test_creating_store_can_save_its_bank_accounts_atomically(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('admin.stores.store'), ['name' => 'New Store', 'slug' => 'new-store', 'category_id' => StoreCategory::query()->firstOrFail()->id, 'province' => 'Kalimantan Barat', 'city' => 'Singkawang', 'district' => 'Singkawang Barat', 'address_line' => 'Private address', 'bank_accounts' => [$this->bank()]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_bank_accounts', ['store_id' => $user->store->id, 'account_number' => '0012345678']);
    }

    public function test_bank_account_from_another_store_cannot_be_edited_or_reassigned(): void
    {
        $bank = BankAccount::factory()->create();
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->patch(route('admin.stores.update', $other), $this->data($other, ['bank_accounts' => [$this->bank(['id' => $bank->id, 'account_holder' => 'Stolen'])]]))->assertSessionHasErrors('bank_accounts.0.id');
        $this->assertDatabaseHas('rintis_bank_accounts', ['id' => $bank->id, 'store_id' => $bank->store_id, 'account_holder' => $bank->account_holder]);
    }

    public function test_invalid_bank_details_and_excessive_accounts_are_rejected(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, ['bank_accounts' => [$this->bank(['bank_name' => '', 'account_number' => 'abc', 'account_holder' => '', 'is_active' => 'invalid'])]]))
            ->assertSessionHasErrors(['bank_accounts.0.bank_name', 'bank_accounts.0.account_holder', 'bank_accounts.0.account_number', 'bank_accounts.0.is_active']);
        $this->patch(route('admin.stores.update', $store), $this->data($store, ['bank_accounts' => array_fill(0, 11, $this->bank())]))->assertSessionHasErrors('bank_accounts');
        $this->assertDatabaseCount('rintis_bank_accounts', 0);
    }

    public function test_legacy_settings_updates_preserve_existing_bank_accounts(): void
    {
        $bank = BankAccount::factory()->create();
        $store = Store::findOrFail($bank->store_id);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_bank_accounts', ['id' => $bank->id, 'is_active' => true]);
    }

    public function test_bank_details_are_not_sent_to_public_pages(): void
    {
        $store = Store::factory()->create(['is_listed' => true]);
        BankAccount::factory()->create(['store_id' => $store->id]);
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->missing('bankAccounts')->missing('store.bank_accounts')->missing('store.account_number'));
        $this->get(route('directory'))->assertOk()->assertInertia(fn (Assert $page) => $page->missing('stores.data.0.bank_accounts')->missing('stores.data.0.account_number'));
    }

    public function test_starting_point_reports_actual_setup_state_and_catalog_counts(): void
    {
        $store = Store::factory()->create();
        BankAccount::factory()->create(['store_id' => $store->id]);
        $this->actingAs($store->owner)->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('setup.bankAccounts', 1)->where('setup.products', 0)->where('setup.categories', 0)->where('setup.pinReady', false)->where('setup.locationReady', true));
    }

    public function test_fulfillment_settings_save_and_return_to_overview(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'pickup_enabled' => true, 'pickup_instructions' => 'Collect at front desk', 'delivery_enabled' => true,
            'latitude' => 0.9, 'longitude' => 109, 'delivery_fee_mode' => 'road_distance',
            'delivery_rate_per_km' => 5000, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 10000,
        ]))->assertSessionHasNoErrors()->assertRedirectToRoute('admin.stores.show', $store);
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'pickup_enabled' => true, 'delivery_rate_per_km' => 5000, 'delivery_max_distance_m' => 10000]);
    }

    public function test_delivery_requires_a_real_pin_and_valid_pricing_without_partial_writes(): void
    {
        $store = Store::factory()->create(['latitude' => null, 'longitude' => null]);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'delivery_enabled' => true, 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 0, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000,
        ]))->assertSessionHasErrors('delivery_enabled');
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'delivery_enabled' => false]);
        $this->patch(route('admin.stores.update', $store), $this->data($store, [
            'delivery_enabled' => true, 'delivery_fee_mode' => 'road_distance', 'delivery_rate_per_km' => -1, 'delivery_max_distance_m' => 0,
        ]))->assertSessionHasErrors(['delivery_rate_per_km', 'delivery_min_distance_m', 'delivery_max_distance_m']);
    }

    public function test_zero_fixed_fee_is_valid_and_disabling_delivery_keeps_saved_settings(): void
    {
        $store = Store::factory()->create(['latitude' => 0, 'longitude' => 109]);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'delivery_enabled' => true, 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 0, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000,
        ]))->assertSessionHasNoErrors();
        $this->patch(route('admin.stores.update', $store), $this->data($store, ['delivery_enabled' => false]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'delivery_enabled' => false, 'delivery_fixed_fee' => 0, 'delivery_max_distance_m' => 5000]);
    }

    public function test_setup_completion_requires_readiness_and_owner_access(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.setup', $store))->assertUnprocessable();
        $this->assertNull($store->fresh()->setup_completed_at);
        $this->actingAs(User::factory()->create())->patch(route('admin.stores.setup', $store))->assertNotFound();
    }

    public function test_completed_setup_stays_hidden_after_later_catalog_changes(): void
    {
        $store = Store::factory()->create(['is_listed' => true, 'latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => true]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $store->catalogProducts()->create(['name' => 'Latte', 'catalog_category_id' => $category->id, 'price' => 17000, 'is_visible' => true]);
        $store->bankAccounts()->create($this->bank(['account_number' => '0012345678']));
        $this->actingAs($store->owner)->patch(route('admin.stores.setup', $store))->assertRedirectToRoute('admin.stores.show', $store);
        $this->assertNotNull($store->fresh()->setup_completed_at);
        $store->catalogProducts()->update(['is_visible' => false]);
        $this->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('setupCompleted', true));
    }

    public function test_saved_bank_account_can_be_deleted_without_removing_other_accounts(): void
    {
        $store = Store::factory()->create();
        $deleted = $store->bankAccounts()->create($this->bank(['account_number' => '00123456']));
        $kept = $store->bankAccounts()->create($this->bank(['account_number' => '00987654']));

        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'deleted_bank_account_ids' => [$deleted->id], 'bank_accounts' => [],
        ]))->assertSessionHasNoErrors()->assertRedirectToRoute('admin.stores.show', $store);

        $this->assertModelMissing($deleted);
        $this->assertModelExists($kept);
    }

    public function test_deleting_another_stores_bank_account_is_rejected_without_changes(): void
    {
        $store = Store::factory()->create();
        $own = $store->bankAccounts()->create($this->bank(['account_number' => '00123456']));
        $other = Store::factory()->create()->bankAccounts()->create($this->bank(['account_number' => '00987654']));

        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'deleted_bank_account_ids' => [$own->id, $other->id],
        ]))->assertSessionHasErrors('deleted_bank_account_ids.1');

        $this->assertModelExists($own);
        $this->assertModelExists($other);
    }

    public function test_minimum_delivery_distance_cannot_exceed_maximum_and_does_not_save(): void
    {
        $store = Store::factory()->create(['latitude' => 0.9, 'longitude' => 109]);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, [
            'delivery_enabled' => true, 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 0,
            'delivery_min_distance_m' => 6000, 'delivery_max_distance_m' => 5000,
        ]))->assertSessionHasErrors('delivery_min_distance_m');
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'delivery_enabled' => false, 'delivery_min_distance_m' => null]);
    }

    public function test_seller_can_toggle_payment_methods_and_older_settings_payload_preserves_them(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, ['cash_enabled' => false, 'bank_transfer_enabled' => true]))->assertSessionHasNoErrors();
        $this->assertFalse($store->fresh()->cash_enabled);
        $this->assertTrue($store->fresh()->bank_transfer_enabled);
        $this->get(route('admin.stores.edit', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('store.cash_enabled', false)->where('store.bank_transfer_enabled', true));
        $this->patch(route('admin.stores.update', $store), $this->data($store))->assertSessionHasNoErrors();
        $this->assertFalse($store->fresh()->cash_enabled);
        $this->patch(route('admin.stores.update', $store), $this->data($store, ['cash_enabled' => true, 'bank_transfer_enabled' => false]))->assertSessionHasNoErrors();
        $this->assertTrue($store->fresh()->cash_enabled);
        $this->assertFalse($store->fresh()->bank_transfer_enabled);
        $this->patch(route('admin.stores.update', $store), $this->data($store, ['cash_enabled' => 'invalid']))->assertSessionHasErrors('cash_enabled');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->patch(route('admin.stores.update', $store), $this->data($store, ['cash_enabled' => false]))->assertNotFound();
        $this->assertTrue($store->fresh()->cash_enabled);
    }
}
