export interface Document {
    id: number;
    name: string;
    status: 'uploaded' | 'processing' | 'ready';
    createdAt: string;
}

export interface UploadItem {
    id: string;
    name: string;
    progress: number;
    status: 'uploading' | 'success' | 'error';
    errorMessage?: string;
}
