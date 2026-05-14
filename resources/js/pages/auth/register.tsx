import { Head } from '@inertiajs/react';

export default function Register() {
    return (
        <>
            <Head title="Registrasi dinonaktifkan" />
            <div className="text-center text-sm text-muted-foreground">
                Registrasi user baru hanya tersedia melalui CRUD user admin.
            </div>
        </>
    );
}

Register.layout = {
    title: 'Registrasi dinonaktifkan',
    description: 'Silakan hubungi admin untuk membuat user baru.',
};
