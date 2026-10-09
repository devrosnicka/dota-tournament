import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * App name with a small glowing diamond, as shown in the headers.
 */
export default function Brand({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex min-w-0 items-center gap-2.5 font-display font-bold tracking-[0.12em] uppercase',
                className,
            )}
        >
            <span
                aria-hidden
                className="size-[0.55em] shrink-0 rotate-45 bg-primary shadow-[0_0_10px_var(--primary)]"
            />
            <span className="truncate">{name}</span>
        </span>
    );
}

/**
 * The current tournament phase with a live dot.
 */
export function PhaseBadge({
    label,
    className,
}: {
    label: string;
    className?: string;
}) {
    return (
        <Badge variant="secondary" className={cn('gap-1.5', className)}>
            <span className="size-1.5 rounded-full bg-gold shadow-[0_0_6px_var(--gold)] motion-safe:animate-pulse" />
            {label}
        </Badge>
    );
}
