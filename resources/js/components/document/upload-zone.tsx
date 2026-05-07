import { router } from '@inertiajs/react';
import { CheckCircle, Search, UploadIcon, X, XCircle } from 'lucide-react';
import { useRef, useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import { Button } from '@/components/ui/button';

interface UploadItem {
    id: string;
    name: string;
    progress: number;
    status: 'uploading' | 'success' | 'error';
    errorMessage?: string;
}

function parseErrorMessage(responseText: string): string {
    try {
        const body = JSON.parse(responseText);
        const firstError = Object.values(body.errors ?? {})[0];

        if (Array.isArray(firstError) && firstError.length > 0) {
            return firstError[0] as string;
        }

        return body.message ?? 'Upload failed. Please try again.';
    } catch {
        return 'Upload failed. Please try again.';
    }
}

function uploadFile(
    file: File,
    onProgress: (pct: number) => void,
    onDone: () => void,
    onError: (message: string) => void,
): void {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append('file', file);

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            onProgress(Math.round((e.loaded / e.total) * 100));
        }
    });

    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            onDone();
        } else {
            onError(parseErrorMessage(xhr.responseText));
        }
    });

    xhr.addEventListener('error', () =>
        onError('Upload failed. Please try again.'),
    );

    xhr.open('POST', DocumentController.store.url());
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader(
        'X-XSRF-TOKEN',
        decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        ),
    );
    xhr.send(formData);
}

interface DocumentUploadZoneProps {
    onUploadComplete?: () => void;
}

export function DocumentUploadZone({ onUploadComplete }: DocumentUploadZoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [uploads, setUploads] = useState<UploadItem[]>([]);

    const updateUpload = (id: string, patch: Partial<UploadItem>) => {
        setUploads((prev) =>
            prev.map((u) => (u.id === id ? { ...u, ...patch } : u)),
        );
    };

    const enqueue = (files: FileList | File[]) => {
        const list = Array.from(files);
        const newItems: UploadItem[] = list.map((file) => ({
            id: `${file.name}-${Date.now()}-${Math.random()}`,
            name: file.name,
            progress: 0,
            status: 'uploading',
        }));

        setUploads((prev) => [...prev, ...newItems]);

        let completed = 0;
        const total = newItems.length;

        newItems.forEach((item, i) => {
            uploadFile(
                list[i],
                (pct) => updateUpload(item.id, { progress: pct }),
                () => {
                    updateUpload(item.id, { progress: 100, status: 'success' });
                    completed++;

                    if (completed === total) {
                        router.reload({ only: ['documents', 'stats'] });
                        onUploadComplete?.();
                    }

                    setTimeout(() => dismiss(item.id), 2000);
                },
                (message) =>
                    updateUpload(item.id, {
                        status: 'error',
                        errorMessage: message,
                    }),
            );
        });
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files?.length) {
            enqueue(e.target.files);
            e.target.value = '';
        }
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);

        if (e.dataTransfer.files.length) {
            enqueue(e.dataTransfer.files);
        }
    };

    const dismiss = (id: string) => {
        setUploads((prev) => prev.filter((u) => u.id !== id));
    };

    return (
        <div className="flex w-full flex-col gap-3">
            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    setIsDragging(true);
                }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={handleDrop}
                className={`flex h-64 w-full flex-col items-center justify-center rounded-xl border border-dashed bg-input transition-colors dark:border-sidebar-border ${isDragging ? 'border-primary bg-primary/5' : ''}`}
            >
                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                    <UploadIcon className="mb-4 h-6 w-6" />
                    <p className="mb-2 text-sm">
                        {isDragging
                            ? 'Drop files here'
                            : 'Drag & drop files here or click to browse'}
                    </p>
                    <p className="mb-4 text-xs">
                        Max. File Size:{' '}
                        <span className="font-semibold">30MB</span>
                    </p>
                    <Button
                        variant="default"
                        onClick={() => inputRef.current?.click()}
                    >
                        <Search className="me-1.5 h-4 w-4" aria-hidden="true" />
                        Browse file
                    </Button>
                </div>
            </div>

            <input
                ref={inputRef}
                type="file"
                multiple
                className="hidden"
                onChange={handleChange}
            />

            {uploads.length > 0 && (
                <div className="flex flex-col gap-2">
                    {uploads.map((upload) => (
                        <div
                            key={upload.id}
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
                                            onClick={() => dismiss(upload.id)}
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
                    ))}
                </div>
            )}
        </div>
    );
}
