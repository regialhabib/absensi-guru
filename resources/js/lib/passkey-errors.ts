const PASSKEY_ERROR_MESSAGES: Record<string, string> = {
    'An unknown error occurred.': 'Terjadi kesalahan. Silakan coba lagi.',
    'Passkeys are not supported in this browser.':
        'Browser ini belum mendukung kunci sandi.',
    'The passkey operation was cancelled.': 'Proses kunci sandi dibatalkan.',
    'This device is already registered as a passkey.':
        'Perangkat ini sudah terdaftar sebagai kunci sandi.',
};

/**
 * The passkey client library only reports English messages, so they are
 * mapped here. Messages sent by the server are already translated.
 */
export function translatePasskeyError(message: string): string {
    if (message.startsWith('Request failed')) {
        return PASSKEY_ERROR_MESSAGES['An unknown error occurred.'];
    }

    return PASSKEY_ERROR_MESSAGES[message] ?? message;
}
