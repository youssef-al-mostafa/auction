import { Link, router, usePage } from '@inertiajs/react';
import { Search, User } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { browse, dashboard, home, login, register } from '@/routes';

export const PublicHeader = () => {
    const { auth } = usePage().props;
    const [term, setTerm] = useState('');

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();

        router.get(browse().url, term ? { search: term } : {});
    };

    return (
        <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-4 py-3">
                <Link
                    href={home()}
                    className="text-lg font-bold tracking-tight"
                >
                    Auction House
                </Link>

                <form
                    onSubmit={submitSearch}
                    className="order-last flex w-full min-w-0 flex-1 items-center gap-2 sm:order-none sm:w-auto"
                >
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder="Search for products"
                            className="pl-9"
                            aria-label="Search for products"
                        />
                    </div>

                    <Button type="submit">Search</Button>
                </form>

                <nav className="flex items-center gap-2">
                    <Button variant="ghost" asChild>
                        <Link href={browse()}>Browse</Link>
                    </Button>

                    {auth.user ? (
                        <Button variant="ghost" asChild>
                            <Link href={dashboard()}>
                                <User />
                                My Account
                            </Link>
                        </Button>
                    ) : (
                        <>
                            <Button variant="ghost" asChild>
                                <Link href={login()}>Log in</Link>
                            </Button>
                            <Button asChild>
                                <Link href={register()}>Register</Link>
                            </Button>
                        </>
                    )}
                </nav>
            </div>
        </header>
    );
};
