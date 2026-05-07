import { Search, UploadIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useUploadQueue } from '@/hooks/use-upload-queue';
import { UploadZoneItem } from './upload-zone-item';

interface DocumentUploadZoneProps {
    onUploadComplete?: () => void;
}

export function DocumentUploadZone({
    onUploadComplete,
}: DocumentUploadZoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const { uploads, enqueue, dismiss } = useUploadQueue(onUploadComplete);

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
                        <UploadZoneItem
                            upload={upload}
                            onDismiss={dismiss}
                            key={upload.id}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
