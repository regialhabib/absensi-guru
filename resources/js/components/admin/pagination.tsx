import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

export default function Pagination({
    paginator,
}: {
    paginator: Pick<
        Paginated<unknown>,
        'current_page' | 'last_page' | 'total' | 'links'
    >;
}) {
    if (paginator.last_page <= 1) {
        return (
            <p className="text-sm text-muted-foreground">
                {paginator.total} data
            </p>
        );
    }

    const previousUrl = paginator.links[0]?.url ?? null;
    const nextUrl = paginator.links[paginator.links.length - 1]?.url ?? null;

    return (
        <div className="flex items-center justify-between gap-2">
            <p className="text-sm text-muted-foreground">
                Halaman {paginator.current_page} dari {paginator.last_page} (
                {paginator.total} data)
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!previousUrl}
                >
                    {previousUrl ? (
                        <Link href={previousUrl} preserveScroll>
                            <ChevronLeft /> Sebelumnya
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft /> Sebelumnya
                        </span>
                    )}
                </Button>
                <Button variant="outline" size="sm" asChild disabled={!nextUrl}>
                    {nextUrl ? (
                        <Link href={nextUrl} preserveScroll>
                            Berikutnya <ChevronRight />
                        </Link>
                    ) : (
                        <span>
                            Berikutnya <ChevronRight />
                        </span>
                    )}
                </Button>
            </div>
        </div>
    );
}
