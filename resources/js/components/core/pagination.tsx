import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';

export const Pagination = ({ links }: { links: PaginationLink[] }) => {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-1">
            {links.map((link) => {
                const label = (
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                );

                if (!link.url) {
                    return (
                        <Button
                            key={link.label}
                            variant="ghost"
                            size="sm"
                            disabled
                        >
                            {label}
                        </Button>
                    );
                }

                return (
                    <Button
                        key={link.label}
                        variant={link.active ? 'default' : 'ghost'}
                        size="sm"
                        asChild
                    >
                        <Link href={link.url} preserveScroll>
                            {label}
                        </Link>
                    </Button>
                );
            })}
        </div>
    );
};
