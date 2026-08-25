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
            <div className="mx-auto flex max-w-7xl flex-wrap items-center gap-x-2 gap-y-3 px-4 py-3 sm:gap-x-4">
                <Link
                    href={home()}
                    className="shrink-0 text-base font-bold tracking-tight sm:text-lg"
                >
                    Auction House
                </Link>

                <form
                    onSubmit={submitSearch}
                    className="order-last flex w-full min-w-0 items-center gap-2 sm:order-none sm:w-auto sm:flex-1"
                >
                    <div className="relative min-w-0 flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder="Search for products"
                            className="pl-9"
                            aria-label="Search for products"
                        />
                    </div>

                    <Button type="submit" className="shrink-0">
                        Search
                    </Button>
                </form>

                <nav className="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={browse()}>Browse</Link>
                    </Button>

                    {auth.user ? (
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={dashboard()}>
                                <User />
                                <span className="hidden sm:inline">
                                    My Account
                                </span>
                            </Link>
                        </Button>
                    ) : (
                        <>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={login()}>Log in</Link>
                            </Button>
                            <Button size="sm" asChild>
                                <Link href={register()}>Register</Link>
                            </Button>
                        </>
                    )}
                </nav>
            </div>
        </header>
    );
};
