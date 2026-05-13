import {
    FileText,
    CheckCircle,
    Loader2,
    CheckCheck,
    Ban,
    ClockAlert,
} from 'lucide-react';

interface DocumentStatsProps {
    total: number;
    ready: number;
    processing: number;
    pendingApproval: number;
    uploaded: number;
    failed: number;
}

const stats = [
    { label: 'Total Documents', key: 'total' as const, icon: FileText },
    { label: 'Uploaded', key: 'uploaded' as const, icon: CheckCheck },
    { label: 'Processing', key: 'processing' as const, icon: Loader2 },
    {
        label: 'Waiting for Approval',
        key: 'pendingApproval' as const,
        icon: ClockAlert,
    },
    { label: 'Ready to Chat', key: 'ready' as const, icon: CheckCircle },
    { label: 'Failed', key: 'failed' as const, icon: Ban },
];

export function DocumentStats({
    total,
    ready,
    processing,
    pendingApproval,
    uploaded,
    failed,
}: DocumentStatsProps) {
    const values = {
        total,
        ready,
        processing,
        pendingApproval,
        uploaded,
        failed,
    };

    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-6">
            {stats.map(({ label, key, icon: Icon }) => (
                <div
                    key={key}
                    className="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                >
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Icon className="h-4 w-4" />
                        <span className="text-sm">{label}</span>
                    </div>
                    <span className="text-3xl font-semibold">
                        {values[key]}
                    </span>
                </div>
            ))}
        </div>
    );
}
