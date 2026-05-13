import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { approve, file, preview } from '@/routes/documents';
import type { Document } from '@/types';

interface PreviewData {
    text: string;
    extractionMethod: string | null;
    ocrConfidence: number | null;
}

interface DocumentPreviewModalProps {
    document: Document | null;
    open: boolean;
    onClose: () => void;
}

export function DocumentPreviewModal({
    document,
    open,
    onClose,
}: DocumentPreviewModalProps) {
    const [data, setData] = useState<PreviewData | null>(null);
    const [editedText, setEditedText] = useState<string | null>(null);
    const [isEditing, setIsEditing] = useState(false);

    useEffect(() => {
        if (!open || !document) {
            return;
        }

        const controller = new AbortController();

        fetch(preview.url(document.id), { signal: controller.signal })
            .then((res) => res.json())
            .then((json: PreviewData) => setData(json))
            .catch(() => {});

        return () => controller.abort();
    }, [open, document]);

    const handleClose = () => {
        setData(null);
        setEditedText(null);
        setIsEditing(false);
        onClose();
    };

    const handleStartEditing = () => {
        setEditedText(data?.text ?? '');
        setIsEditing(true);
    };

    const handleCancelEditing = () => {
        setEditedText(null);
        setIsEditing(false);
    };

    const handleApprove = () => {
        if (!document) {
            return;
        }

        router.post(
            approve.url(document.id),
            editedText !== null ? { text: editedText } : {},
            { onSuccess: handleClose },
        );
    };

    const isLoading = open && !data;

    const lowConfidence =
        data?.ocrConfidence !== null &&
        data?.ocrConfidence !== undefined &&
        data.ocrConfidence < 70;

    const isImage = document?.mimeType.startsWith('image/');
    const fileUrl = document ? file.url(document.id) : null;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <DialogContent className="flex max-h-[85vh] max-w-5xl flex-col sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-3">
                        {document?.name}
                        {data?.extractionMethod && (
                            <Badge
                                variant="secondary"
                                className="font-mono text-xs"
                            >
                                {data.extractionMethod}
                            </Badge>
                        )}
                        {data?.ocrConfidence !== null &&
                            data?.ocrConfidence !== undefined && (
                                <Badge
                                    variant={
                                        lowConfidence
                                            ? 'destructive'
                                            : 'default'
                                    }
                                    className="text-xs"
                                >
                                    {data.ocrConfidence}% confidence
                                </Badge>
                            )}
                    </DialogTitle>
                </DialogHeader>

                <div className="grid min-h-0 flex-1 grid-cols-2 gap-4">
                    <div className="flex min-h-0 flex-col overflow-hidden rounded-md border bg-muted/30">
                        {fileUrl && isImage && (
                            <img
                                src={fileUrl}
                                alt={document?.name}
                                className="h-full w-full object-contain"
                            />
                        )}
                        {fileUrl && !isImage && (
                            <iframe
                                src={fileUrl}
                                title={document?.name}
                                className="h-full w-full"
                            />
                        )}
                    </div>

                    <div className="min-h-0 overflow-y-auto rounded-md border bg-muted/30 p-4">
                        {isLoading && (
                            <div className="flex h-32 items-center justify-center text-sm text-muted-foreground">
                                Loading preview...
                            </div>
                        )}
                        {data && isEditing && (
                            <textarea
                                className="h-full w-full resize-none rounded-md border bg-background p-2 font-mono text-sm"
                                value={editedText ?? ''}
                                onChange={(e) => setEditedText(e.target.value)}
                            />
                        )}
                        {data && !isEditing && (
                            <pre className="font-sans text-sm leading-relaxed whitespace-pre-wrap">
                                {data.text || 'No text could be extracted.'}
                            </pre>
                        )}
                    </div>
                </div>

                {lowConfidence && (
                    <p className="text-sm text-muted-foreground">
                        Confidence is low — consider using AI for better
                        results.
                    </p>
                )}

                <DialogFooter className="gap-2">
                    {!isEditing ? (
                        <Button
                            variant="outline"
                            onClick={handleStartEditing}
                        >
                            Edit Text
                        </Button>
                    ) : (
                        <Button
                            variant="outline"
                            onClick={handleCancelEditing}
                        >
                            Cancel Edit
                        </Button>
                    )}

                    <Button variant="outline" onClick={handleClose}>
                        Close
                    </Button>
                    {data?.extractionMethod === 'tesseract' && (
                        <Button variant="secondary" disabled>
                            Try with AI
                        </Button>
                    )}
                    <Button onClick={handleApprove}>Approve</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
