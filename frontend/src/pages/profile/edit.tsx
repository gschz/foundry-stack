import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import ProfileLayout from '@/layouts/profile-layout';
import DeleteUser from '@/pages/profile/components/auto-delete';
import { BasicInfoCard } from '@/pages/profile/components/basic-info-card';
import type { ProfilePageProps as ProfilePagePropsLocal } from '@/pages/profile/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';

type ProfileEditPageProps = PageProps &
  ProfilePagePropsLocal & {
    mustVerifyEmail?: boolean;
    status?: string | null;
  };

export default function ProfileEditPage() {
  const { auth, contextualNavItems, breadcrumbs, mustVerifyEmail, status } =
    usePage<ProfileEditPageProps>().props;

  const userData = extractUserData(auth.user);
  const isStaffUser = !!userData;

  const initialName = userData?.name ?? '';
  const initialEmail = userData?.email ?? '';

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs ?? []}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Perfil" />

      <ProfileLayout>
        <div className="space-y-8">
          <HeadingSmall title="Perfil" description="Gestiona tu información personal" />

          <BasicInfoCard
            initialName={initialName}
            initialEmail={initialEmail}
            mustVerifyEmail={Boolean(mustVerifyEmail)}
            isStaffUser={isStaffUser}
            emailVerifiedAt={userData?.email_verified_at ?? null}
            status={status ?? ''}
          />
        </div>

        <DeleteUser />
      </ProfileLayout>
    </AppLayout>
  );
}
