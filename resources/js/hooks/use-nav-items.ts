import { usePage } from '@inertiajs/react';
import { mainNavItems } from '@/config/navigation';
import type { NavItem } from '@/types';

export const useNavItems = (): NavItem[] => {
    const { auth } = usePage().props;

    return mainNavItems.filter(
        (item) =>
            (!item.permission || auth.permissions.includes(item.permission)) &&
            (!item.unlessPermission ||
                !auth.permissions.includes(item.unlessPermission)),
    );
};
