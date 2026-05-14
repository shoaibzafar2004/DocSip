import { router } from '@inertiajs/react';
import { FileText, ScanSearch, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/documents';
import type { Document } from '@/types';
import { DocumentPreviewModal } from './preview-modal';

interface DocumentListProps {
    documents: Document[];
}

function computeCooldownLabel(aiLastAttemptedAt: string | null): string | null {
    if (!aiLastAttemptedAt) {
        return null;
    }

    const retryAt = new Date(new Date(aiLastAttemptedAt).getTime() + 2 * 60 * 60 * 1000);
    const remaining = retryAt.getTime() - Date.now();

    if (remaining <= 0) {
        return null;
    }

    const hours = Math.floor(remaining / (1000 * 60 * 60));
    const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));

    return hours > 0 ? `Try again in ${hours}h ${minutes}m` : `Try again in ${minutes}m`;
}

export function DocumentList({ documents }: DocumentListProps) {
    const [previewDoc, setPreviewDoc] = useState<Document | null>(null);
    const [cooldownLabel, setCooldownLabel] = useState<string | null>(null);

    const handleOpenPreview = (doc: Document) => {
        setPreviewDoc(doc);
        setCooldownLabel(computeCooldownLabel(doc.aiLastAttemptedAt));
    };

    if (documents.length === 0) {
        return (
            <div className="flex min-h-64 flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <FileText className="h-10 w-10 text-muted-foreground/40" />
                <p className="text-sm text-muted-foreground">
                    No documents yet. Upload one to get started.
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                {documents.map((doc) => (
                    <div
                        key={doc.id}
                        className="flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-accent"
                    >
                        <div className="flex items-center gap-3">
                            <FileText className="h-4 w-4 shrink-0 text-muted-foreground" />
                            <span className="text-sm font-medium">{doc.name}</span>
                        </div>
                        <div className="flex items-center gap-4">
                            {doc.statusMessage && (
                                <span
                                    className={`text-xs ${doc.status === 'failed' ? 'text-destructive' : 'text-muted-foreground'}`}
                                >
                                    {doc.statusMessage}
                                </span>
                            )}
                            <span className="text-xs text-muted-foreground">
                                {doc.createdAt}
                            </span>
                            <Badge
                                variant={
                                    doc.status === 'ready'
                                        ? 'default'
                                        : doc.status === 'failed'
                                          ? 'destructive'
                                          : 'secondary'
                                }
                            >
                                {doc.status === 'ready'
                                    ? 'Ready'
                                    : doc.status === 'processing'
                                      ? 'Processing'
                                      : doc.status === 'pending_approval'
                                        ? 'Review'
                                        : doc.status === 'failed'
                                          ? 'Failed'
                                          : 'Uploaded'}
                            </Badge>

                            {(doc.status === 'pending_approval' ||
                                doc.status === 'failed') && (
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => handleOpenPreview(doc)}
                                >
                                    <ScanSearch className="h-4 w-4" />
                                </Button>
                            )}

                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="outline" size="icon">
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>
                                            Delete document
                                        </AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Are you sure you want to delete{' '}
                                            <span className="font-medium text-foreground">
                                                {doc.name}
                                            </span>
                                            ? This action cannot be undone.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>
                                            Cancel
                                        </AlertDialogCancel>
                                        <AlertDialogAction
                                            onClick={() =>
                                                router.delete(destroy(doc.id))
                                            }
                                        >
                                            Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>
                ))}
            </div>

            <DocumentPreviewModal
                document={previewDoc}
                open={previewDoc !== null}
                onClose={() => setPreviewDoc(null)}
                cooldownLabel={cooldownLabel}
            />
        </>
    );
}
