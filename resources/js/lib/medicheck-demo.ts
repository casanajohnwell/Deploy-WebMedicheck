export type MedicineStatus = 'available' | 'low' | 'out_of_stock' | 'expired';
export type BookingStatus =
    | 'pending'
    | 'approved'
    | 'declined'
    | 'claimed'
    | 'unclaimed';
export type SmsStatus = 'sent' | 'failed' | 'queued';
export type SmsType = 'approval' | 'decline' | 'ready' | 'reminder';

export type Medicine = {
    id: number;
    image?: string;
    name: string;
    category: string;
    healthCenter: string;
    quantity: number;
    unit: string;
    expiration: string;
    status: MedicineStatus;
};

export const medicineStatusOptions: {
    value: Exclude<MedicineStatus, 'expired'>;
    label: string;
}[] = [
    { value: 'available', label: 'Available' },
    { value: 'low', label: 'Low stock' },
    { value: 'out_of_stock', label: 'Out of stock' },
];

export function medicineStatusLabel(status: MedicineStatus): string {
    if (status === 'out_of_stock') {
        return 'Out of stock';
    }

    if (status === 'low') {
        return 'Low stock';
    }

    if (status === 'expired') {
        return 'Expired';
    }

    return 'Available';
}

export type Booking = {
    id: number;
    seeker: string;
    medicine: string;
    quantity: number;
    healthCenter: string;
    requestedAt: string;
    status: BookingStatus;
    phone: string;
    notes: string;
    claimDays?: number | null;
    claimBy?: string;
};

export type SmsLog = {
    id: number;
    to: string;
    recipient: string;
    message: string;
    status: SmsStatus;
    sentAt: string;
    type: SmsType;
    healthCenter: string;
};

export const healthCenters = [
    'Brgy. San Isidro Health Center',
    'Brgy. Mabini Health Center',
    'Brgy. Poblacion Health Center',
    'Brgy. Santa Cruz Health Center',
    'Brgy. Magupange Health Center',
];

const magupange = healthCenters[4];

export const demoMedicines: Medicine[] = [
    {
        id: 1,
        name: 'Paracetamol 500mg',
        category: 'Analgesic',
        healthCenter: healthCenters[0],
        quantity: 240,
        unit: 'tablets',
        expiration: '2027-03-12',
        status: 'available',
    },
    {
        id: 2,
        name: 'Amoxicillin 500mg',
        category: 'Antibiotic',
        healthCenter: healthCenters[1],
        quantity: 18,
        unit: 'capsules',
        expiration: '2026-11-02',
        status: 'low',
    },
    {
        id: 3,
        name: 'Losartan 50mg',
        category: 'Antihypertensive',
        healthCenter: healthCenters[2],
        quantity: 96,
        unit: 'tablets',
        expiration: '2027-08-20',
        status: 'available',
    },
    {
        id: 4,
        name: 'Salbutamol 2mg',
        category: 'Respiratory',
        healthCenter: healthCenters[0],
        quantity: 8,
        unit: 'tablets',
        expiration: '2026-09-18',
        status: 'low',
    },
    {
        id: 5,
        name: 'Metformin 500mg',
        category: 'Antidiabetic',
        healthCenter: healthCenters[3],
        quantity: 0,
        unit: 'tablets',
        expiration: '2026-04-01',
        status: 'expired',
    },
    {
        id: 6,
        name: 'ORS Sachet',
        category: 'Rehydration',
        healthCenter: healthCenters[2],
        quantity: 150,
        unit: 'sachets',
        expiration: '2028-01-15',
        status: 'available',
    },
    {
        id: 7,
        name: 'Cetirizine 10mg',
        category: 'Antihistamine',
        healthCenter: healthCenters[1],
        quantity: 42,
        unit: 'tablets',
        expiration: '2027-05-09',
        status: 'available',
    },
    {
        id: 8,
        name: 'Ferrous Sulfate',
        category: 'Supplement',
        healthCenter: healthCenters[3],
        quantity: 12,
        unit: 'tablets',
        expiration: '2026-10-30',
        status: 'low',
    },
    {
        id: 9,
        name: 'Paracetamol 500mg',
        category: 'Analgesic',
        healthCenter: magupange,
        quantity: 86,
        unit: 'tablets',
        expiration: '2027-06-12',
        status: 'available',
    },
    {
        id: 10,
        name: 'Amoxicillin 500mg',
        category: 'Antibiotic',
        healthCenter: magupange,
        quantity: 14,
        unit: 'capsules',
        expiration: '2026-12-08',
        status: 'low',
    },
    {
        id: 11,
        name: 'ORS Sachet',
        category: 'Rehydration',
        healthCenter: magupange,
        quantity: 40,
        unit: 'sachets',
        expiration: '2028-02-01',
        status: 'available',
    },
    {
        id: 12,
        name: 'Metformin 500mg',
        category: 'Antidiabetic',
        healthCenter: magupange,
        quantity: 0,
        unit: 'tablets',
        expiration: '2026-05-20',
        status: 'expired',
    },
];

export const demoBookings: Booking[] = [
    {
        id: 1042,
        seeker: 'Maria Santos',
        medicine: 'Paracetamol 500mg',
        quantity: 10,
        healthCenter: healthCenters[0],
        requestedAt: '2026-09-03 08:14',
        status: 'pending',
        phone: '0917 555 0142',
        notes: 'For fever, 3 days supply.',
    },
    {
        id: 1041,
        seeker: 'Jose Ramirez',
        medicine: 'Amoxicillin 500mg',
        quantity: 21,
        healthCenter: healthCenters[1],
        requestedAt: '2026-09-03 07:48',
        status: 'pending',
        phone: '0918 222 8810',
        notes: 'Prescribed by BHWs for infection.',
    },
    {
        id: 1040,
        seeker: 'Ana Villanueva',
        medicine: 'Losartan 50mg',
        quantity: 30,
        healthCenter: healthCenters[2],
        requestedAt: '2026-09-02 16:22',
        status: 'approved',
        phone: '0920 441 2291',
        notes: 'Monthly maintenance.',
    },
    {
        id: 1039,
        seeker: 'Pedro Cruz',
        medicine: 'Salbutamol 2mg',
        quantity: 8,
        healthCenter: healthCenters[0],
        requestedAt: '2026-09-02 14:05',
        status: 'declined',
        phone: '0915 300 1188',
        notes: 'Low stock at this center.',
    },
    {
        id: 1038,
        seeker: 'Liza Mendoza',
        medicine: 'ORS Sachet',
        quantity: 6,
        healthCenter: healthCenters[2],
        requestedAt: '2026-09-01 11:37',
        status: 'claimed',
        phone: '0998 774 2200',
        notes: 'Picked up at Poblacion.',
    },
    {
        id: 1037,
        seeker: 'Carlo Dizon',
        medicine: 'Cetirizine 10mg',
        quantity: 14,
        healthCenter: healthCenters[1],
        requestedAt: '2026-08-31 09:12',
        status: 'claimed',
        phone: '0916 888 4412',
        notes: 'Allergy relief.',
    },
    {
        id: 1050,
        seeker: 'Rhea Calo',
        medicine: 'Paracetamol 500mg',
        quantity: 10,
        healthCenter: magupange,
        requestedAt: '2026-09-03 09:10',
        status: 'pending',
        phone: '0917 441 2201',
        notes: 'Fever, Magupange resident.',
    },
    {
        id: 1049,
        seeker: 'Junrel Plaza',
        medicine: 'Amoxicillin 500mg',
        quantity: 21,
        healthCenter: magupange,
        requestedAt: '2026-09-03 08:40',
        status: 'pending',
        phone: '0918 330 1190',
        notes: 'For infection. Pickup at Magupange.',
    },
    {
        id: 1048,
        seeker: 'Neneng Elorde',
        medicine: 'ORS Sachet',
        quantity: 4,
        healthCenter: magupange,
        requestedAt: '2026-09-02 15:18',
        status: 'approved',
        phone: '0920 118 4422',
        notes: 'Ready at Brgy. Magupange Health Center.',
    },
];

export const demoSmsLogs: SmsLog[] = [
    {
        id: 21,
        to: '0920 441 2291',
        recipient: 'Ana Villanueva',
        message:
            'MediCheck: Your request #1040 for Losartan 50mg was APPROVED. Please claim at Brgy. Poblacion Health Center.',
        status: 'sent',
        sentAt: '2026-09-02 16:30',
        type: 'approval',
        healthCenter: healthCenters[2],
    },
    {
        id: 20,
        to: '0915 300 1188',
        recipient: 'Pedro Cruz',
        message:
            'MediCheck: Your request #1039 for Salbutamol 2mg was DECLINED due to low stock. Try another health center.',
        status: 'sent',
        sentAt: '2026-09-02 14:12',
        type: 'decline',
        healthCenter: healthCenters[0],
    },
    {
        id: 19,
        to: '0998 774 2200',
        recipient: 'Liza Mendoza',
        message:
            'MediCheck: Request #1038 is ready for pickup at Brgy. Poblacion Health Center.',
        status: 'sent',
        sentAt: '2026-09-01 11:50',
        type: 'ready',
        healthCenter: healthCenters[2],
    },
    {
        id: 18,
        to: '0916 888 4412',
        recipient: 'Carlo Dizon',
        message:
            'MediCheck: Reminder — Cetirizine 10mg stock is available at Brgy. Mabini Health Center.',
        status: 'queued',
        sentAt: '2026-09-03 08:00',
        type: 'reminder',
        healthCenter: healthCenters[1],
    },
    {
        id: 17,
        to: '0918 222 8810',
        recipient: 'Jose Ramirez',
        message:
            'MediCheck: We could not deliver your status SMS. Please check your request in the app.',
        status: 'failed',
        sentAt: '2026-09-03 07:55',
        type: 'approval',
        healthCenter: healthCenters[1],
    },
    {
        id: 30,
        to: '0920 118 4422',
        recipient: 'Neneng Elorde',
        message:
            'MediCheck: Your request #1048 for ORS Sachet was APPROVED. Claim at Brgy. Magupange Health Center.',
        status: 'sent',
        sentAt: '2026-09-02 15:30',
        type: 'approval',
        healthCenter: magupange,
    },
    {
        id: 29,
        to: '0917 441 2201',
        recipient: 'Rhea Calo',
        message:
            'MediCheck: Reminder — Paracetamol is available at Brgy. Magupange Health Center.',
        status: 'queued',
        sentAt: '2026-09-03 09:20',
        type: 'reminder',
        healthCenter: magupange,
    },
];

export const demoMetrics = {
    pendingBookings: 2,
    approvedToday: 1,
    lowStockItems: 3,
    expiringSoon: 2,
    totalMedicines: demoMedicines.length,
    healthCenters: healthCenters.length,
    smsSentThisWeek: 18,
    seekersServed: 126,
};
