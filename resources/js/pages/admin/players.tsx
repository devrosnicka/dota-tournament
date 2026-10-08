import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatTime } from '@/lib/format';
import { destroy, loginCode, update, withdraw } from '@/routes/admin/players';

type PlayerRow = {
    id: number;
    nick: string;
    status: 'active' | 'withdrawn';
    withdrawnFromRound: number | null;
    registeredAt: string | null;
    rankingSubmitted: boolean;
    loginCode: { code: string; expiresAt: string } | null;
};

type Withdrawal = { firstRound: number; lastRound: number };

type Props = {
    players: PlayerRow[];
    canDelete: boolean;
    showRankingStatus: boolean;
    withdrawal: Withdrawal | null;
};

export default function Players({
    players,
    canDelete,
    showRankingStatus,
    withdrawal,
}: Props) {
    return (
        <>
            <Head title="Hráči" />
            <Card>
                <CardHeader>
                    <CardTitle>Hráči ({players.length})</CardTitle>
                    <CardDescription>
                        Kód slouží k přihlášení na jiném zařízení, platí 15
                        minut a jde použít jen jednou.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {players.length === 0 ? (
                        <p className="text-muted-foreground">
                            Zatím se nikdo nezaregistroval.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {players.map((player) => (
                                <PlayerItem
                                    key={player.id}
                                    player={player}
                                    canDelete={canDelete}
                                    showRankingStatus={showRankingStatus}
                                    withdrawal={withdrawal}
                                />
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

function PlayerItem({
    player,
    canDelete,
    showRankingStatus,
    withdrawal,
}: {
    player: PlayerRow;
    canDelete: boolean;
    showRankingStatus: boolean;
    withdrawal: Withdrawal | null;
}) {
    return (
        <li className="flex flex-wrap items-center gap-x-3 gap-y-2 py-3">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <span className="truncate font-medium">{player.nick}</span>
                {showRankingStatus && (
                    <Badge
                        variant={
                            player.rankingSubmitted ? 'secondary' : 'outline'
                        }
                    >
                        {player.rankingSubmitted
                            ? 'hodnocení odesláno'
                            : 'bez hodnocení'}
                    </Badge>
                )}
                {player.status === 'withdrawn' && (
                    <Badge variant="outline">
                        odstoupil od {player.withdrawnFromRound}. kola
                    </Badge>
                )}
            </div>
            {player.loginCode && (
                <span className="font-mono text-lg font-bold">
                    {player.loginCode.code}
                    <span className="ml-2 font-sans text-xs font-normal text-muted-foreground">
                        do {formatTime(player.loginCode.expiresAt)}
                    </span>
                </span>
            )}
            <div className="flex gap-1">
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() =>
                        router.visit(loginCode(player.id), {
                            preserveScroll: true,
                        })
                    }
                >
                    Kód
                </Button>
                <RenameDialog player={player} />
                {withdrawal && player.status === 'active' && (
                    <WithdrawDialog player={player} withdrawal={withdrawal} />
                )}
                {canDelete && (
                    <ConfirmDialog
                        trigger={
                            <Button size="sm" variant="ghost">
                                Smazat
                            </Button>
                        }
                        title={`Smazat hráče ${player.nick}?`}
                        description="Smaže se i jeho hodnocení ostatních hráčů."
                        confirmLabel="Smazat"
                        destructive
                        onConfirm={() =>
                            router.visit(destroy(player.id), {
                                preserveScroll: true,
                            })
                        }
                    />
                )}
            </div>
        </li>
    );
}

function RenameDialog({ player }: { player: PlayerRow }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="ghost">
                    Přejmenovat
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Přejmenovat hráče {player.nick}</DialogTitle>
                </DialogHeader>
                <Form
                    {...update.form(player.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Input
                                    name="nick"
                                    defaultValue={player.nick}
                                    required
                                    maxLength={24}
                                    aria-label="Nová přezdívka"
                                />
                                <InputError message={errors.nick} />
                            </div>
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    Uložit
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function WithdrawDialog({
    player,
    withdrawal,
}: {
    player: PlayerRow;
    withdrawal: Withdrawal;
}) {
    const [open, setOpen] = useState(false);
    const rounds = Array.from(
        { length: withdrawal.lastRound - withdrawal.firstRound + 2 },
        (_, index) => withdrawal.firstRound + index,
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="ghost">
                    Odstoupení
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Odstoupení hráče {player.nick}</DialogTitle>
                </DialogHeader>
                <Form
                    {...withdraw.form(player.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <p className="text-sm text-muted-foreground">
                                Hráč si ponechá získané body, ale do finále
                                nepostoupí. Kola od zvoleného dál se přegenerují
                                pro zbylé hráče, odehraná kola se nemění. Vrátit
                                to nejde.
                            </p>
                            <select
                                name="round"
                                aria-label="Od kola"
                                defaultValue={withdrawal.firstRound}
                                className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                            >
                                {rounds.map((round) => (
                                    <option key={round} value={round}>
                                        {round > withdrawal.lastRound
                                            ? 'po základní části (bez přegenerování)'
                                            : `od ${round}. kola`}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.round} />
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Odstoupit
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
