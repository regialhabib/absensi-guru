import type { PropsWithChildren } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = PropsWithChildren<{
    title: string;
    description: string;
    className?: string;
}>;

export default function SettingsCard({
    title,
    description,
    className,
    children,
}: Props) {
    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle>
                    <h3>{title}</h3>
                </CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
