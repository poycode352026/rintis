<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/Auth/AuthLayout.vue';
import FormField from '../../Components/Shared/FormField.vue';
const props = defineProps({ submitUrl: String, loginUrl: String, homeUrl: String });
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post(props.submitUrl, { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Create your account" />
    <AuthLayout :home-url="homeUrl">
        <p class="text-xs font-semibold tracking-widest text-stone-500 uppercase">Your Rintis account</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Start with you.</h1>
        <p class="mt-3 text-sm leading-6 text-stone-500">Create your account and start setting up your store.</p>
        <form class="mt-8 flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-model="form.name" name="name" label="Your name" autocomplete="name" :error="form.errors.name" required maxlength="100" />
            <FormField v-model="form.email" name="email" label="Email address" type="email" autocomplete="username" :error="form.errors.email" required maxlength="255" />
            <FormField v-model="form.password" name="password" label="Password" type="password" autocomplete="new-password" :error="form.errors.password" required />
            <p class="-mt-3 text-xs text-stone-500">Use at least 12 characters.</p>
            <FormField v-model="form.password_confirmation" name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" :error="form.errors.password_confirmation" required />
            <button :disabled="form.processing" class="rounded-xl bg-[#504941] px-5 py-3 font-medium text-white hover:bg-stone-700 disabled:opacity-50">{{ form.processing ? 'Creating account…' : 'Create account' }}</button>
        </form>
        <p class="mt-6 text-sm text-stone-600">Already have an account? <Link :href="loginUrl" class="font-semibold underline underline-offset-4">Sign in</Link></p>
    </AuthLayout>
</template>
