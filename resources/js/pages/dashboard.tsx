import { Head, usePoll } from '@inertiajs/react';
import { DocumentList } from '@/components/document/list';
import { DocumentStats } from '@/components/document/stats';
import { DocumentUploadZone } from '@/components/document/upload-zone';
import { dashboard } from '@/routes';
import type { Document } from '@/types';

interface DashboardProps {
    documents: Document[];
    stats: {
        total: number;
        ready: number;
        processing: number;
        uploaded: number;
        failed: number;
    };
}

export default function Dashboard({
    documents = [],
    stats = { total: 0, ready: 0, processing: 0, uploaded: 0, failed: 0 },
}: DashboardProps) {
    const hasPending = documents.some(
        (doc) => doc.status === 'uploaded' || doc.status === 'processing',
    );

    usePoll(3000, { only: ['documents', 'stats'] }, { autoStart: hasPending });

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <DocumentUploadZone />
                <DocumentStats
                    total={stats.total}
                    ready={stats.ready}
                    processing={stats.processing}
                    uploaded={stats.uploaded}
                    failed={stats.failed}
                />
                <DocumentList documents={documents} />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
