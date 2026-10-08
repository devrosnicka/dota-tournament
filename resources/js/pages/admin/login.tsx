import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/admin/login';

export default function AdminLogin() {
    return (
        <>
            <Head title="Administrace" />
            <h2 className="text-lg font-semibold">Administrace</h2>
            <Form {...store.form()} className="grid gap-4">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="password">Heslo</Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autoFocus
                                autoComplete="current-password"
                            />
                            <InputError message={errors.password} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Přihlásit
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}
