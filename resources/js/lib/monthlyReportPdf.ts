import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

export type PdfReportLine = {
    date: string;
    seeker: string;
    medicine: string;
    quantity: number;
    status: string;
};

export type PdfReportInput = {
    healthCenter: string;
    monthTitle: string;
    fileMonth: string;
    preparedBy: string;
    mostTaken: { medicine: string; quantity: number } | null;
    summaryRows: [string, number][];
    lines: PdfReportLine[];
};

const RED: [number, number, number] = [220, 38, 38];

async function logoPng(): Promise<string | null> {
    try {
        const image = new Image();
        image.src = '/images/medicheck-logo.svg';
        await image.decode();

        const canvas = document.createElement('canvas');
        canvas.width = 128;
        canvas.height = 128;
        canvas.getContext('2d')?.drawImage(image, 0, 0, 128, 128);

        return canvas.toDataURL('image/png');
    } catch {
        return null;
    }
}

function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

export async function downloadMonthlyReportPdf(input: PdfReportInput): Promise<void> {
    const doc = new jsPDF({ unit: 'pt', format: 'letter' });
    const left = 43;
    const right = doc.internal.pageSize.getWidth() - 43;
    const generatedAt = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Manila',
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date());

    const logo = await logoPng();
    const titleX = logo ? left + 42 : left;

    if (logo) {
        doc.addImage(logo, 'PNG', left, 36, 32, 32);
    }

    doc.setFont('helvetica', 'bold').setFontSize(16).text('MediCheck Monthly Report', titleX, 52);
    doc.setFont('helvetica', 'normal').setFontSize(10).setTextColor(90).text(input.healthCenter, titleX, 66);
    doc.setDrawColor(...RED).setLineWidth(1.5).line(left, 78, right, 78);

    doc.setTextColor(20).setFontSize(10);
    const mostTaken = input.mostTaken
        ? `${input.mostTaken.medicine} (${input.mostTaken.quantity} pcs)`
        : 'None';
    const meta: [string, string][] = [
        ['Month', input.monthTitle],
        ['Generated', generatedAt],
        ['Prepared by', input.preparedBy],
        ['Most taken medicine', mostTaken],
    ];
    meta.forEach(([label, value], index) => {
        const x = index % 2 === 0 ? left : left + (right - left) / 2;
        const y = 98 + Math.floor(index / 2) * 16;
        doc.setFont('helvetica', 'bold').text(`${label}:`, x, y);
        doc.setFont('helvetica', 'normal').text(value, x + doc.getTextWidth(`${label}: `), y);
    });

    const tableStyle = {
        theme: 'grid' as const,
        styles: { fontSize: 9, cellPadding: 4, lineColor: [204, 204, 204] as [number, number, number] },
        headStyles: { fillColor: [254, 242, 242] as [number, number, number], textColor: 20, fontStyle: 'bold' as const },
        margin: { left, right: 43 },
    };

    doc.setFont('helvetica', 'bold').setFontSize(11).setTextColor(...RED).text('SUMMARY', left, 146);
    autoTable(doc, {
        ...tableStyle,
        startY: 152,
        head: [['Item', 'Count']],
        body: input.summaryRows.map(([label, value]) => [label, String(value)]),
        columnStyles: { 1: { halign: 'right', cellWidth: 70 } },
    });

    const recordsY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY + 24;
    doc.setFont('helvetica', 'bold').setFontSize(11).setTextColor(...RED).text('BOOKING RECORDS', left, recordsY);
    autoTable(doc, {
        ...tableStyle,
        startY: recordsY + 6,
        head: [['Date', 'Seeker', 'Medicine', 'Qty', 'Status']],
        body: input.lines.length
            ? input.lines.map((line) => [line.date, line.seeker, line.medicine, String(line.quantity), capitalize(line.status)])
            : [[{ content: 'No bookings were recorded for this month.', colSpan: 5 }]],
        columnStyles: { 3: { halign: 'right', cellWidth: 40 } },
    });

    let signY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY + 70;
    if (signY > doc.internal.pageSize.getHeight() - 60) {
        doc.addPage();
        signY = 100;
    }
    const half = (right - left) / 2;
    doc.setDrawColor(20).setLineWidth(0.8);
    doc.line(left, signY, left + half - 24, signY);
    doc.line(left + half + 24, signY, right, signY);
    doc.setFont('helvetica', 'normal').setFontSize(9).setTextColor(20);
    doc.text('Prepared by (Nurse)', left + (half - 24) / 2, signY + 12, { align: 'center' });
    doc.text('Noted by (Municipal Health Officer)', left + half + 24 + (half - 24) / 2, signY + 12, { align: 'center' });

    doc.save(`medicheck-report-${input.fileMonth}.pdf`);
}
