<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import LogOut from 'lucide-svelte/icons/log-out';
    import Settings from 'lucide-svelte/icons/settings';
    import {
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
    } from '@/components/ui/dropdown-menu';
    import UserInfo from '@/components/UserInfo.svelte';
    import { toUrl } from '@/lib/utils';
    import { logout } from '@/routes';
    import { edit } from '@/routes/profile';
    import type { User } from '@/types';

    let {
        user,
    }: {
        user: User;
    } = $props();
</script>

<DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <UserInfo {user} showEmail={true} />
    </div>
</DropdownMenuLabel>
<DropdownMenuSeparator />
<DropdownMenuGroup>
    <DropdownMenuItem>
        {#snippet child({ props })}
            <Link
                class={props.class}
                href={toUrl(edit())}
                prefetch
                onclick={props.onClick}
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        {/snippet}
    </DropdownMenuItem>
</DropdownMenuGroup>
<DropdownMenuSeparator />
<DropdownMenuItem>
    {#snippet child({ props })}
        <button
            class={props.class}
            type="button"
            data-test="logout-button"
            onclick={() => {
                props.onClick?.();
                router.post(logout());
            }}
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </button>
    {/snippet}
</DropdownMenuItem>