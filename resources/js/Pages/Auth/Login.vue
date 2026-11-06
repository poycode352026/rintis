<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/Auth/AuthLayout.vue';
import FormField from '../../Components/Shared/FormField.vue';
const props = defineProps({ submitUrl: String, registerUrl: String, homeUrl: String });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(props.submitUrl, { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Sign in" />
    <AuthLayout :home-url="homeUrl">
        <p class="text-xs font-semibold tracking-widest text-stone-500 uppercase">Your Rintis account</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Welcome back.</h1>
        <p class="mt-3 text-sm leading-6 text-stone-500">Sign in to manage your store on Rintis.</p>
        <form class="mt-8 flex flex-col gap-5" @submit.prevent="submit">
            <FormField v-model="form.email" name="email" label="Email address" type="email" autocomplete="username" :error="form.errors.email" required maxlength="255" />
            <FormField v-model="form.password" name="password" label="Password" type="password" autocomplete="current-password" :error="form.errors.password" required />
            <label class="flex items-center gap-3 text-sm"><input v-model="form.remember" type="checkbox" class="size-4 accent-stone-700">Remember me</label>
            <button :disabled="form.processing" class="rounded-xl bg-[#504941] px-5 py-3 font-medium text-white hover:bg-stone-700 disabled:opacity-50">{{ form.processing ? 'Signing in…' : 'Sign in' }}</button>
        </form>
        <p class="mt-6 text-sm text-stone-600">New here? <Link :href="registerUrl" class="font-semibold underline underline-offset-4">Create an account</Link></p>
    </AuthLayout>
</template>
