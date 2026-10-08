import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    winner: 'A' | 'B' | null;
    killsA: number | null;
    killsB: number | null;
    compact?: boolean;
    onSuccess?: () => void;
};

/**
 * Winner and kills of both teams as shown in the game's score.
 */
export default function ResultForm({
    form,
    winner,
    killsA,
    killsB,
    compact = false,
    onSuccess,
}: Props) {
    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className="grid gap-3"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid grid-cols-2 gap-2">
                        {(['A', 'B'] as const).map((side) => (
                            <label
                                key={side}
                                className={cn(
                                    'flex cursor-pointer items-center justify-center rounded-md border font-medium has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-primary-foreground',
                                    compact ? 'h-9 text-sm' : 'h-11',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="winner"
                                    value={side}
                                    defaultChecked={winner === side}
                                    required
                                    className="sr-only"
                                />
                                Vyhrál tým {side}
                            </label>
                        ))}
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        {(['a', 'b'] as const).map((side) => (
                            <div key={side} className="grid gap-1">
                                <Label htmlFor={`kills_${side}_${form.action}`}>
                                    Killy týmu {side.toUpperCase()}
                                </Label>
                                <Input
                                    id={`kills_${side}_${form.action}`}
                                    name={`kills_${side}`}
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    max={300}
                                    required
                                    defaultValue={
                                        (side === 'a' ? killsA : killsB) ?? ''
                                    }
                                />
                            </div>
                        ))}
                    </div>
                    <InputError
                        message={
                            errors.winner ?? errors.kills_a ?? errors.kills_b
                        }
                    />
                    <Button
                        type="submit"
                        disabled={processing}
                        size={compact ? 'sm' : 'default'}
                    >
                        {processing && <Spinner />}
                        {winner ? 'Opravit výsledek' : 'Uložit výsledek'}
                    </Button>
                </>
            )}
        </Form>
    );
}
