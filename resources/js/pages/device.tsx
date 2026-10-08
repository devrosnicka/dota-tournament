import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { formatTime } from '@/lib/format';
import { store } from '@/routes/device';

type Props = {
    loginCode: { code: string; expiresAt: string } | null;
};

export default function Device({ loginCode }: Props) {
    return (
        <>
            <Head title="Přihlásit jiné zařízení" />
            <Card>
                <CardHeader>
                    <CardTitle>Přihlásit jiné zařízení</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <p className="text-muted-foreground">
                        Na druhém zařízení otevři tuhle stránku, zvol přihlášení
                        kódem a opiš kód. Kód platí 15 minut a jde použít jen
                        jednou.
                    </p>
                    {loginCode && (
                        <div className="rounded-lg border bg-muted/50 p-4 text-center">
                            <div className="font-mono text-5xl font-bold tracking-[0.3em]">
                                {loginCode.code}
                            </div>
                            <div className="mt-2 text-sm text-muted-foreground">
                                platí do {formatTime(loginCode.expiresAt)}
                            </div>
                        </div>
                    )}
                    <Form {...store.form()}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={processing}
                                variant={loginCode ? 'outline' : 'default'}
                                className="w-full"
                            >
                                {processing && <Spinner />}
                                {loginCode
                                    ? 'Vygenerovat nový kód'
                                    : 'Vygenerovat kód'}
                            </Button>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </>
    );
}
