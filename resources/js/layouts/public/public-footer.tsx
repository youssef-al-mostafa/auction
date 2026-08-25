import { Link } from '@inertiajs/react';
import { browse } from '@/routes';

export const PublicFooter = () => (
    <footer className="mt-16 border-t bg-muted/40">
        <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-2">
                <span className="font-bold">Auction House</span>
                <p className="max-w-sm text-sm text-muted-foreground">
                    Live and ongoing auctions, with every bid permanently
                    logged.
                </p>
            </div>

            <nav className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                <Link href={browse()} className="hover:text-foreground">
                    Browse auctions
                </Link>
                <span>Auction rules</span>
                <span>Support</span>
            </nav>
        </div>

        <div className="border-t py-4 text-center text-xs text-muted-foreground">
            © {new Date().getFullYear()} Auction House
        </div>
    </footer>
);
