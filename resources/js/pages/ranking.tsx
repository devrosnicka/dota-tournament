import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    restrictToParentElement,
    restrictToVerticalAxis,
} from '@dnd-kit/modifiers';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, router, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronUp, GripVertical } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store } from '@/routes/ranking';

type RankedPlayer = { id: number; nick: string };

type Props = {
    players: RankedPlayer[];
    status: 'none' | 'saved' | 'outdated';
    editable: boolean;
};

export default function Ranking({ players, status, editable }: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [order, setOrder] = useState(players);
    const [processing, setProcessing] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const savedIds = status === 'saved' ? players.map((p) => p.id) : null;
    const dirty =
        savedIds === null ||
        savedIds.length !== order.length ||
        savedIds.some((id, index) => order[index].id !== id);

    function move(from: number, to: number) {
        if (to >= 0 && to < order.length) {
            setOrder(arrayMove(order, from, to));
        }
    }

    function onDragEnd({ active, over }: DragEndEvent) {
        if (over && active.id !== over.id) {
            move(
                order.findIndex((p) => p.id === active.id),
                order.findIndex((p) => p.id === over.id),
            );
        }
    }

    function save() {
        router.post(
            store.url(),
            { order: order.map((p) => p.id) },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <>
            <Head title="Hodnocení hráčů" />
            <div className="grid gap-4 pb-20">
                <div className="grid gap-1">
                    <div className="flex items-center justify-between gap-2">
                        <h1 className="text-2xl font-bold">Hodnocení hráčů</h1>
                        <StatusBadge
                            status={status}
                            dirty={dirty}
                            editable={editable}
                        />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {editable
                            ? 'Seřaď ostatní od nejlepšího po nejhoršího. Táhni za úchyt, nebo použij šipky. Tvoje pořadí nikdo jiný neuvidí.'
                            : 'Hodnocení je uzavřené. Takhle jsi seřadil ostatní hráče.'}
                    </p>
                </div>

                <InputError message={errors.order} />

                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    modifiers={[
                        restrictToVerticalAxis,
                        restrictToParentElement,
                    ]}
                    onDragEnd={onDragEnd}
                >
                    <SortableContext
                        items={order.map((p) => p.id)}
                        strategy={verticalListSortingStrategy}
                    >
                        <ol className="grid gap-2">
                            {order.map((player, index) => (
                                <SortableRow
                                    key={player.id}
                                    player={player}
                                    position={index + 1}
                                    editable={editable}
                                    isFirst={index === 0}
                                    isLast={index === order.length - 1}
                                    onUp={() => move(index, index - 1)}
                                    onDown={() => move(index, index + 1)}
                                />
                            ))}
                        </ol>
                    </SortableContext>
                </DndContext>
            </div>

            {editable && (
                <div className="fixed inset-x-0 bottom-0 border-t bg-background/95 p-3 backdrop-blur">
                    <div className="mx-auto max-w-2xl">
                        <Button
                            className="h-11 w-full"
                            onClick={save}
                            disabled={processing || !dirty}
                        >
                            {processing && <Spinner />}
                            {dirty ? 'Uložit pořadí' : 'Uloženo'}
                        </Button>
                    </div>
                </div>
            )}
        </>
    );
}

function StatusBadge({
    status,
    dirty,
    editable,
}: {
    status: Props['status'];
    dirty: boolean;
    editable: boolean;
}) {
    if (!editable) {
        return null;
    }

    if (status === 'saved' && !dirty) {
        return <Badge>Uloženo</Badge>;
    }

    if (status === 'saved') {
        return <Badge variant="outline">Neuložené změny</Badge>;
    }

    if (status === 'outdated') {
        return <Badge variant="destructive">Přibyli hráči, ulož znovu</Badge>;
    }

    return <Badge variant="outline">Zatím neodesláno</Badge>;
}

function SortableRow({
    player,
    position,
    editable,
    isFirst,
    isLast,
    onUp,
    onDown,
}: {
    player: RankedPlayer;
    position: number;
    editable: boolean;
    isFirst: boolean;
    isLast: boolean;
    onUp: () => void;
    onDown: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        setActivatorNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: player.id, disabled: !editable });

    return (
        <li
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
            className={cn(
                'flex h-12 items-center gap-2 rounded-lg border bg-card pr-1 pl-3',
                isDragging && 'relative z-10 shadow-lg ring-2 ring-primary',
            )}
        >
            <span className="w-6 text-right text-sm text-muted-foreground tabular-nums">
                {position}.
            </span>
            <span className="min-w-0 flex-1 truncate font-medium">
                {player.nick}
            </span>
            {editable && (
                <>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={`Posunout ${player.nick} výš`}
                        disabled={isFirst}
                        onClick={onUp}
                    >
                        <ChevronUp />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={`Posunout ${player.nick} níž`}
                        disabled={isLast}
                        onClick={onDown}
                    >
                        <ChevronDown />
                    </Button>
                    <button
                        ref={setActivatorNodeRef}
                        type="button"
                        aria-label={`Přetáhnout ${player.nick}`}
                        className="flex size-10 cursor-grab touch-none items-center justify-center rounded-md text-muted-foreground hover:bg-accent active:cursor-grabbing"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical />
                    </button>
                </>
            )}
        </li>
    );
}
