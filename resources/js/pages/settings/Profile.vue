<script setup lang="ts">
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const avatarPreview = ref(user.avatar || null);
const avatarFile = ref<File | null>(null);
const serverErrors = ref<any>({});

function onAvatarChange(e: Event) {
    const t = e.target as HTMLInputElement;
    if (!t.files || t.files.length === 0) return;
    const f = t.files[0];
    const reader = new FileReader();
    reader.onload = () => {
        avatarPreview.value = String(reader.result || '');
    };
    reader.readAsDataURL(f);
    avatarFile.value = f;
}

async function submitProfile(e: Event) {
    const formEl = e.target as HTMLFormElement;
    const fd = new FormData(formEl);
    if (avatarFile.value) fd.set('avatar', avatarFile.value as Blob);

    const action = ProfileController.update.form().action;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const res = await fetch(action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
    });

    if (res.status === 422) {
        // validation errors
        const body = await res.json().catch(() => ({}));
        serverErrors.value = body.errors || {};
        return;
    }

    if (!res.ok) {
        // generic failure: reload to show server flash or errors
        window.location.reload();
        return;
    }

    // success: reload to refresh user data
    window.location.reload();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                    @submit.prevent="submitProfile"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="serverErrors.name || errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="serverErrors.email || errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="avatar">Avatar</Label>
                        <input type="file" accept="image/*" @change="onAvatarChange" />
                        <div v-if="avatarPreview" class="mt-2">
                            <img :src="avatarPreview" class="h-16 w-16 rounded-full object-cover" />
                        </div>
                    </div>



                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
