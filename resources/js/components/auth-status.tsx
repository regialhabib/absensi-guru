export default function AuthStatus({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p
            role="status"
            className="mb-6 rounded-lg border bg-muted px-4 py-3 text-sm font-medium"
        >
            {message}
        </p>
    );
}
