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
import { approve, preview } from '@/routes/documents';
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
        onClose();
    };

    const handleApprove = () => {
        if (!document) {
            return;
        }

        router.post(approve.url(document.id), {}, { onSuccess: handleClose });
    };

    const isLoading = open && !data;

    const lowConfidence =
        data?.ocrConfidence !== null &&
        data?.ocrConfidence !== undefined &&
        data.ocrConfidence < 70;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <DialogContent className="flex max-h-[80vh] max-w-2xl flex-col">
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
                                        lowConfidence ? 'destructive' : 'default'
                                    }
                                    className="text-xs"
                                >
                                    {data.ocrConfidence}% confidence
                                </Badge>
                            )}
                    </DialogTitle>
                </DialogHeader>

                <div className="min-h-0 flex-1 overflow-y-auto rounded-md border bg-muted/30 p-4">
                    {isLoading && (
                        <div className="flex h-32 items-center justify-center text-sm text-muted-foreground">
                            Loading preview...
                        </div>
                    )}
                    {data && (
                        <pre className="whitespace-pre-wrap font-sans text-sm leading-relaxed">
                            {data.text || 'No text could be extracted.'}
                        </pre>
                    )}
                </div>

                {lowConfidence && (
                    <p className="text-sm text-muted-foreground">
                        Confidence is low — consider using AI for better results.
                    </p>
                )}

                <DialogFooter className="gap-2">
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
