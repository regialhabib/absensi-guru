// Components
import { Form, Head } from '@inertiajs/react';
import AuthStatus from '@/components/auth-status';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Lupa kata sandi" />

            <AuthStatus message={status} />

            <div className="space-y-6">
                <Form {...email.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Alamat email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@contoh.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <Button
                                size="lg"
                                className="w-full"
                                disabled={processing}
                                data-test="email-password-reset-link-button"
                            >
                                {processing && <Spinner />}
                                Kirim tautan reset
                            </Button>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Sudah ingat?</span>
                    <TextLink href={login()}>Kembali ke halaman masuk</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Lupa kata sandi',
    description:
        'Masukkan email Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.',
};
