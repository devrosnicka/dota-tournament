import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { howItWorks, register } from '@/routes';
import { login as adminLogin } from '@/routes/admin';
import { store } from '@/routes/login';

export default function Login({
    registrationOpen,
}: {
    registrationOpen: boolean;
}) {
    return (
        <>
            <Head title="Přihlášení" />
            <div className="grid gap-1">
                <h2 className="text-lg font-semibold">Přihlášení kódem</h2>
                <p className="text-sm text-muted-foreground">
                    Čtyřmístný kód si vygeneruješ na zařízení, kde už jsi
                    přihlášený (Menu → Přihlásit jiné zařízení), nebo ti ho dá
                    admin.
                </p>
            </div>

            <Form {...store.form()} className="grid gap-4">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="code">Kód</Label>
                            <Input
                                id="code"
                                name="code"
                                required
                                autoFocus
                                inputMode="numeric"
                                pattern="[0-9]{4}"
                                maxLength={4}
                                autoComplete="one-time-code"
                                className="h-14 text-center text-3xl tracking-[0.5em]"
                            />
                            <InputError message={errors.code} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Přihlásit se
                        </Button>
                    </>
                )}
            </Form>

            <div className="grid gap-1 text-center text-sm text-muted-foreground">
                {registrationOpen && (
                    <p>
                        Ještě nejsi registrovaný?{' '}
                        <Link
                            href={register()}
                            className="underline underline-offset-4"
                        >
                            Registrace
                        </Link>
                    </p>
                )}
                <Link
                    href={howItWorks()}
                    className="underline underline-offset-4"
                >
                    Jak turnaj probíhá?
                </Link>
                <Link
                    href={adminLogin()}
                    className="text-xs underline-offset-4 hover:underline"
                >
                    Administrace
                </Link>
            </div>
        </>
    );
}
