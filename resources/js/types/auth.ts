export type User = {
    id: number;
    public_id: string;
    name: string;
    birth_date?: string | null;
    city?: string | null;
    phone?: string | null;
    email: string;
    roles?: string[];
    active_role?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type NotificationItem = {
    id: string;
    title: string;
    text: string;
    url: string;
    created_at: string | null;
    read_at: string | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
