import { WinAnnouncer } from '@/components/app/win-announcer';
import { PublicHeader } from '@/layouts/public/public-header';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <PublicHeader />

            <main className="flex-1 pb-20">{children}</main>

            <WinAnnouncer />
        </div>
    );
}
