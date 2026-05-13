export interface Document {
    id: number;
    name: string;
    status: 'uploaded' | 'processing' | 'pending_approval' | 'ready' | 'failed';
    statusMessage?: string;
    createdAt: string;
}

export interface UploadItem {
    id: string;
    name: string;
    progress: number;
    status: 'uploading' | 'success' | 'error';
    errorMessage?: string;
}
