import { CheckCircle, X, XCircle } from 'lucide-react';
import type { UploadItem } from '@/types/documents';

interface UploadZoneItemProps {
    upload: UploadItem;
    onDismiss: (id: string) => void;
    key: string;
}

export function UploadZoneItem({
    upload,
    onDismiss,
    key,
}: UploadZoneItemProps) {
    return (
        <div
            key={key}
            className="flex items-center gap-3 rounded-lg border border-sidebar-border/70 px-3 py-2.5 dark:border-sidebar-border"
        >
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <div className="flex items-center justify-between gap-2">
                    <span className="truncate text-sm font-medium">
                        {upload.name}
                    </span>
                    <div className="flex shrink-0 items-center gap-1.5">
                        {upload.status === 'uploading' && (
                            <span className="text-xs text-muted-foreground">
                                {upload.progress}%
                            </span>
                        )}
                        {upload.status === 'success' && (
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        )}
                        {upload.status === 'error' && (
                            <XCircle className="h-4 w-4 text-destructive" />
                        )}
                        <button
                            onClick={() => onDismiss(upload.id)}
                            className="rounded p-0.5 hover:bg-accent"
                            aria-label="Dismiss"
                        >
                            <X className="h-3.5 w-3.5 text-muted-foreground" />
                        </button>
                    </div>
                </div>
                {upload.status === 'uploading' && (
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all duration-200"
                            style={{
                                width: `${upload.progress}%`,
                            }}
                        />
                    </div>
                )}
                {upload.status === 'error' && (
                    <p className="text-xs text-destructive">
                        {upload.errorMessage ??
                            'Upload failed. Please try again.'}
                    </p>
                )}
            </div>
        </div>
    );
}
