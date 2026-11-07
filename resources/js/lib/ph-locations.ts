export type LocationNode = {
    name: string;
    postalCode?: string;
    children?: LocationNode[];
};

function city(
    name: string,
    postalCode: string,
    barangays: string[],
): LocationNode {
    return {
        name,
        postalCode,
        children: barangays.map((barangay) => ({ name: barangay })),
    };
}

function province(name: string, cities: LocationNode[]): LocationNode {
    return { name, children: cities };
}

export const phLocations: LocationNode[] = [
    {
        name: 'Metro Manila',
        children: [
            province('Metro Manila', [
                city('Manila', '1000', [
                    'Binondo',
                    'Ermita',
                    'Malate',
                    'Poblacion',
                    'Tondo',
                ]),
                city('Quezon City', '1100', [
                    'Diliman',
                    'Cubao',
                    'Fairview',
                    'Novaliches',
                ]),
                city('Makati', '1226', ['Poblacion', 'Bel-Air', 'San Lorenzo']),
                city('Pasig', '1600', ['Kapitolyo', 'Ortigas', 'San Antonio']),
            ]),
        ],
    },
    {
        name: 'Mindanao',
        children: [
            province('Agusan Del Norte', [
                city('Butuan City', '8600', ['Libertad', 'Baan', 'Poblacion']),
                city('Cabadbaran', '8605', ['Poblacion 1', 'Poblacion 2']),
            ]),
            province('Agusan Del Sur', [
                city('Prosperidad', '8500', ['Poblacion', 'San Vicente']),
            ]),
            province('Basilan', [
                city('Isabela City', '7300', ['Poblacion', 'Sunrise']),
            ]),
            province('Bukidnon', [
                city('Malaybalay', '8700', ['Poblacion', 'Sumpong']),
            ]),
            province('Camiguin', [
                city('Mambajao', '9100', ['Poblacion', 'Balbagon']),
            ]),
            province('Compostela Valley', [
                city('Nabunturan', '8800', ['Poblacion', 'San Isidro']),
            ]),
            province('Cotabato', [
                city('Kidapawan', '9400', ['Poblacion', 'Lanao']),
            ]),
            province('Davao Del Norte', [
                city('Tagum', '8100', ['Poblacion', 'Apokon']),
            ]),
            province('Davao Del Sur', [
                city('Digos', '8002', ['Poblacion', 'Tres De Mayo']),
            ]),
            province('Davao Oriental', [
                city('Mati', '8200', ['Poblacion', 'Dahican']),
            ]),
            province('Dinagat Islands', [
                city('San Jose', '8412', ['Poblacion', 'Aurelio']),
            ]),
            province('Surigao Del Norte', [
                city('Alegria', '8425', [
                    'Alipao',
                    'Anahaw',
                    'Budlingin',
                    'Camp Eduard (Geotina)',
                    'Ferlda',
                    'Gamuton',
                    'Julio Ouano (Pob.)',
                    'Ombong',
                    'Poblacion (Alegria)',
                    'Pongtud',
                    'San Juan',
                    'San Pedro',
                ]),
                city('Bacuag', '8408', [
                    'Cabugao',
                    'Cambuayon',
                    'Campo',
                    'Dugsangon',
                    'Pautao',
                    'Payapag',
                    'Poblacion',
                    'Pungtod',
                    'Santo Rosario',
                ]),
                city('Burgos', '8424', [
                    'Baybay',
                    'Bitaug',
                    'Matin-ao',
                    'Poblacion 1',
                    'Poblacion 2',
                    'San Mateo',
                ]),
                city('Claver', '8410', [
                    'Bagakay (Pob. West)',
                    'Cabugo',
                    'Cagdianao',
                    'Daywan',
                    'Hayanggabon',
                    'Ladgaron (Pob.)',
                    'Lapinigan',
                    'Magallanes',
                    'Panatao',
                    'Sapa',
                    'Taganito',
                    'Tayaga (Pob. East)',
                    'Urbiztondo',
                    'Wangke',
                ]),
                city('Dapa', '8417', [
                    'Bagakay',
                    'Barangay 1 (Pob.)',
                    'Barangay 10 (Pob.)',
                    'Barangay 11 (Pob.)',
                    'Barangay 12 (Pob.)',
                    'Barangay 13 (Pob.)',
                    'Barangay 2 (Pob.)',
                    'Barangay 3 (Pob.)',
                    'Barangay 4 (Pob.)',
                    'Barangay 5 (Pob.)',
                    'Barangay 6 (Pob.)',
                    'Barangay 7 (Pob.)',
                    'Barangay 8 (Pob.)',
                    'Barangay 9 (Pob.)',
                    'Buenavista',
                    'Cabawa',
                    'Cambas-ac',
                    'Consolacion',
                    'Corregidor',
                    'Dagohoy',
                    'Don Paulino',
                    'Jubang',
                    'Montserrat',
                    'Osmeña',
                    'San Carlos',
                    'San Miguel',
                    'Santa Fe',
                    'Santa Felomina',
                    'Union',
                ]),
                city('Del Carmen', '8418', [
                    'Antipolo',
                    'Bagakay (Alburo)',
                    'Bitoon',
                    'Cabugao',
                    'Cancohoy',
                    'Caub',
                    'Del Carmen (Pob.)',
                    'Domoyog',
                    'Esperanza',
                    'Halian',
                    'Jamoyaon',
                    'Katipunan',
                    'Lobogon',
                    'Mabuhay',
                    'Mahayahay',
                    'Quezon',
                    'San Fernando',
                    'San Jose (Pob.)',
                    'Sayak',
                    'Tuboran',
                ]),
                city('General Luna', '8419', [
                    'Anajawan',
                    'Cabitoonan',
                    'Catangnan',
                    'Consuelo',
                    'Corazon',
                    'Daku',
                    'La Januza',
                    'Libertad',
                    'Magsaysay',
                    'Malinao',
                    'Poblacion I (Purok I)',
                    'Poblacion II (Purok II)',
                    'Poblacion III (Purok III)',
                    'Poblacion IV (Purok IV)',
                    'Poblacion V (Purok V)',
                    'Santa Cruz',
                    'Santa Fe',
                    'Suyangan',
                    'Tawin-tawin',
                ]),
                city('Gigaquit', '8409', [
                    'Alambique (Pob.)',
                    'Anibongan',
                    'Cam-boayon',
                    'Camam-onan',
                    'Ipil (Pob.)',
                    'Lahi',
                    'Mahanub',
                    'Poniente',
                    'San Antonio (Bonot)',
                    'San Isidro',
                    'Sico-sico',
                    'Villaflor',
                    'Villafranca',
                ]),
                city('Mainit', '8407', [
                    'Binga',
                    'Bobona-on',
                    'Cantugas',
                    'Dayano',
                    'Mabini',
                    'Magpayang',
                    'Magsaysay (Pob.)',
                    'Mansayao',
                    'Marayag',
                    'Matin-ao',
                    'Paco',
                    'Quezon (Pob.)',
                    'Roxas',
                    'San Francisco',
                    'San Isidro',
                    'San Jose',
                    'Siana',
                    'Silop',
                    'Tagbuyawan',
                    'Tapi-an',
                    'Tolingon',
                ]),
                city('Malimono', '8402', [
                    'Bunyasan',
                    'Cagtinae',
                    'Can-aga',
                    'Cansayong',
                    'Cantapoy',
                    'Cayawan',
                    'Doro (Binocaran)',
                    'Hanagdong',
                    'Karihatag',
                    'Masgad',
                    'Pili',
                    'San Isidro (Pob.)',
                    'Tinago',
                    'Villariza',
                ]),
                city('Pilar', '8420', [
                    'Asinan (Pob.)',
                    'Caridad',
                    'Centro (Pob.)',
                    'Consolacion',
                    'Datu',
                    'Dayaohay',
                    'Jaboy',
                    'Katipunan',
                    'Maasin',
                    'Mabini',
                    'Mabuhay',
                    'Pilaring (Pob.)',
                    'Punta (Pob.)',
                    'Salvacion',
                    'San Roque',
                ]),
                city('Placer', '8405', [
                    'Amoslog',
                    'Anislagan',
                    'Bad-as',
                    'Boyongan',
                    'Bugas-bugas',
                    'Central (Pob.)',
                    'Ellaperal (Nonok)',
                    'Ipil (Pob.)',
                    'Lakandula',
                    'Mabini',
                    'Macalaya',
                    'Magsaysay (Pob.)',
                    'Magupange',
                    'Pananay-an',
                    'Panhutongan',
                    'San Isidro',
                    'Sani-sani',
                    'Santa Cruz',
                    'Suyoc',
                    'Tagbongabong',
                ]),
                city('San Benito', '8423', [
                    'Bongdo',
                    'Maribojoc',
                    'Nuevo Campo',
                    'San Juan',
                    'Santa Cruz (Pob.)',
                    'Talisay (Pob.)',
                ]),
                city('San Francisco (Anao-aon)', '8401', [
                    'Amontay',
                    'Balite',
                    'Banbanon',
                    'Diaz',
                    'Honrado',
                    'Jubgan',
                    'Linongganan',
                    'Macopa',
                    'Magtangale',
                    'Oslao',
                    'Poblacion',
                ]),
                city('San Isidro', '8421', [
                    'Buhing Calipay',
                    'Del Carmen (Pob.)',
                    'Del Pilar',
                    'Macapagal',
                    'Pacifico',
                    'Pelaez',
                    'Roxas',
                    'San Miguel',
                    'Santa Paz',
                    'Santo Niño',
                    'Tambacan',
                    'Tigasao',
                ]),
                city('Santa Monica (Sapao)', '8422', [
                    'Abad Santos',
                    'Alegria',
                    'Bailan',
                    'Garcia',
                    'Libertad',
                    'Mabini',
                    'Mabuhay (Pob.)',
                    'Magsaysay',
                    'Rizal',
                    'T. Arlan (Pob.)',
                    'Tangbo',
                ]),
                city('Sison', '8404', [
                    'Biyabid',
                    'Gacepan',
                    'Ima',
                    'Lower Patag',
                    'Mabuhay',
                    'Mayag',
                    'Poblacion (San Pedro)',
                    'San Isidro',
                    'San Pablo',
                    'Tagbayani',
                    'Tinogpahan',
                    'Upper Patag',
                ]),
                city('Socorro', '8416', [
                    'Albino Taruc',
                    'Del Pilar',
                    'Helene',
                    'Honrado',
                    'Navarro (Pob.)',
                    'Nueva Estrella',
                    'Pamosaingan',
                    'Rizal (Pob.)',
                    'Salog',
                    'San Roque',
                    'Santa Cruz',
                    'Sering',
                    'Songkoy',
                    'Sudlon',
                ]),
                city('Surigao City', '8400', [
                    'Alang-alang',
                    'Alegria',
                    'Anomar',
                    'Aurora',
                    'Balibayon',
                    'Baybay',
                    'Bilabid',
                    'Bitaugan',
                    'Bonifacio',
                    'Buenavista',
                    'Cabongbongan',
                    'Cagniog',
                    'Cagutsan',
                    'Canlanipa',
                    'Cantiasay',
                    'Capalayan',
                    'Catadman',
                    'Danao',
                    'Danawan',
                    'Day-asan',
                    'Ipil',
                    'Libuac',
                    'Lipata',
                    'Lisondra',
                    'Luna',
                    'Mabini',
                    'Mabua',
                    'Manyagao',
                    'Mapawa',
                    'Mat-i',
                    'Nabago',
                    'Nonoc',
                    'Orok',
                    'Poctoy',
                    'Punta Bilar',
                    'Quezon',
                    'Rizal',
                    'Sabang',
                    'San Isidro',
                    'San Jose',
                    'San Juan',
                    'San Pedro (Hanigad)',
                    'San Roque',
                    'Serna (Bad-asay)',
                    'Sidlakan',
                    'Silop',
                    'Sugbay',
                    'Sukailang',
                    'Taft (Pob.)',
                    'Talisay',
                    'Togbongon',
                    'Trinidad',
                    'Washington (Pob.)',
                    'Zaragoza',
                ]),
                city('Tagana-an', '8403', [
                    'Aurora (Pob.)',
                    'Azucena (Pob.)',
                    'Banban',
                    'Cawilan',
                    'Fabio',
                    'Himamaug',
                    'Laurel',
                    'Lower Libas',
                    'Opong',
                    'Patino',
                    'Sampaguita (Pob.)',
                    'Talavera',
                    'Union',
                    'Upper Libas',
                ]),
                city('Tubod', '8406', [
                    'Capayahan',
                    'Cawilan',
                    'Del Rosario',
                    'Marga',
                    'Motorpool',
                    'Poblacion (Tubod)',
                    'San Isidro',
                    'San Pablo',
                    'Timamana',
                ]),
            ]),
            province('Surigao Del Sur', [
                city('Tandag', '8300', ['Poblacion', 'Mabua']),
            ]),
        ],
    },
    {
        name: 'North Luzon',
        children: [
            province('Benguet', [
                city('Baguio', '2600', ['Session', 'Burnham', 'Poblacion']),
            ]),
            province('Pangasinan', [
                city('Dagupan', '2400', ['Poblacion', 'Bonuan']),
            ]),
            province('Ilocos Norte', [
                city('Laoag', '2900', ['Poblacion', 'Brgy. 1']),
            ]),
        ],
    },
    {
        name: 'South Luzon',
        children: [
            province('Laguna', [
                city('Calamba', '4027', ['Poblacion', 'Canlubang']),
            ]),
            province('Batangas', [
                city('Batangas City', '4200', ['Poblacion', 'Sta. Rita']),
            ]),
            province('Cavite', [
                city('Bacoor', '4102', ['Poblacion', 'Molino']),
            ]),
        ],
    },
    {
        name: 'Visayas',
        children: [
            province('Cebu', [
                city('Cebu City', '6000', [
                    'Lahug',
                    'Mabolo',
                    'Poblacion',
                    'IT Park',
                ]),
                city('Mandaue', '6014', ['Poblacion', 'Banilad']),
            ]),
            province('Iloilo', [
                city('Iloilo City', '5000', ['Poblacion', 'Jaro', 'Molo']),
            ]),
            province('Leyte', [
                city('Tacloban', '6500', ['Poblacion', 'San Jose']),
            ]),
        ],
    },
];

export type FlatLocation = {
    region: string;
    province: string;
    city: string;
    barangay: string;
    postalCode: string;
};

export function flattenLocations(
    nodes: LocationNode[] = phLocations,
): FlatLocation[] {
    const rows: FlatLocation[] = [];

    for (const region of nodes) {
        for (const nextProvince of region.children ?? []) {
            for (const nextCity of nextProvince.children ?? []) {
                for (const barangay of nextCity.children ?? []) {
                    rows.push({
                        region: region.name,
                        province: nextProvince.name,
                        city: nextCity.name,
                        barangay: barangay.name,
                        postalCode: nextCity.postalCode ?? '',
                    });
                }
            }
        }
    }

    return rows;
}

export function postalCodeFor(
    location: Pick<FlatLocation, 'city' | 'province' | 'region'>,
): string {
    const cityNode = childrenAt([location.region, location.province]).find(
        (node) => node.name === location.city,
    );

    return cityNode?.postalCode ?? '';
}

export function childrenAt(path: string[]): LocationNode[] {
    let nodes = phLocations;

    for (const name of path) {
        nodes = nodes.find((node) => node.name === name)?.children ?? [];
    }

    return nodes;
}

export function groupByLetter(
    items: string[],
): { letter: string; items: string[] }[] {
    const map = new Map<string, string[]>();

    for (const item of [...items].sort((a, b) => a.localeCompare(b))) {
        const letter = item.charAt(0).toUpperCase() || '#';
        const group = map.get(letter) ?? [];
        group.push(item);
        map.set(letter, group);
    }

    return [...map.entries()].map(([letter, grouped]) => ({
        letter,
        items: grouped,
    }));
}

export function healthCenterForBarangay(barangay: string): string {
    const clean = barangay.replace(/\s*\(Pob\.?\)$/i, '').trim();

    return `Brgy. ${clean} Health Center`;
}