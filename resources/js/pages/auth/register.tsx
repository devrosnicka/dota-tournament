import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { howItWorks, login } from '@/routes';
import { store } from '@/routes/register';

export default function Register({ open }: { open: boolean }) {
    return (
        <>
            <Head title="Registrace" />
            <div className="grid gap-1">
                <h2 className="text-lg font-semibold">Registrace</h2>
                <p className="text-sm text-muted-foreground">
                    {open
                        ? 'Zvol si přezdívku a zadej kód, který znají jen účastníci.'
                        : 'Registrace je uzavřená.'}
                </p>
            </div>

            {open && (
                <Form {...store.form()} className="grid gap-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="nick">Přezdívka</Label>
                                <Input
                                    id="nick"
                                    name="nick"
                                    required
                                    autoFocus
                                    maxLength={24}
                                    autoComplete="nickname"
                                />
                                <InputError message={errors.nick} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="registration_code">
                                    Registrační kód
                                </Label>
                                <Input
                                    id="registration_code"
                                    name="registration_code"
                                    required
                                    autoComplete="off"
                                />
                                <InputError
                                    message={errors.registration_code}
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Zaregistrovat se
                            </Button>
                        </>
                    )}
                </Form>
            )}

            <div className="grid gap-1 text-center text-sm text-muted-foreground">
                <p>
                    Registroval ses na jiném zařízení?{' '}
                    <Link
                        href={login()}
                        className="underline underline-offset-4"
                    >
                        Přihlas se kódem
                    </Link>
                </p>
                <Link
                    href={howItWorks()}
                    className="underline underline-offset-4"
                >
                    Jak turnaj probíhá?
                </Link>
            </div>
        </>
    );
}
