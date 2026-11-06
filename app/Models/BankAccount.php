<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['bank_name', 'account_holder', 'account_number', 'instructions', 'is_active'])]
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    protected $table = 'rintis_bank_accounts';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
