import { router } from '@inertiajs/react';
import { useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import type { UploadItem } from '@/types/documents';

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

    xhr.addEventListener('error', () => onError('Upload failed. Please try again.'));

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

export function useUploadQueue(onUploadComplete?: () => void) {
    const [uploads, setUploads] = useState<UploadItem[]>([]);

    const updateUpload = (id: string, patch: Partial<UploadItem>) => {
        setUploads((prev) => prev.map((u) => (u.id === id ? { ...u, ...patch } : u)));
    };

    const dismiss = (id: string) => {
        setUploads((prev) => prev.filter((u) => u.id !== id));
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
                (message) => updateUpload(item.id, { status: 'error', errorMessage: message }),
            );
        });
    };

    return { uploads, enqueue, dismiss };
}
