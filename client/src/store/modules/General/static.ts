/**
 * Front page links
 */
export const frontPageLinks = [
  {
    id: 'where',
    icon: 'fas fa-globe-americas',
  },
  {
    id: 'when',
    icon: 'fas fa-chart-line',
  },
  {
    id: 'what',
    icon: 'fas fa-bars',
  }
];

/**
 * Main nav
 */
export const mainNavigation = [
  {
    path: '/search',
    name: 'Catalogue',
    icon: 'search',
    color: 'yellow',
    border: 'border-yellow',
    bg: 'bg-yellow-20',
    hover: 'hover:bg-yellow-20',
  },
  {
    path: '/browse',
    name: 'Browse',
    icon: 'globe-americas',
    color: 'red',
    border: 'border-red',
    bg: 'bg-red-20',
    hover: 'hover:bg-red-20',
    groupHover: 'group-hover:bg-red-20',
    subMenu: [
      {
        path: '/browse/where',
        name: 'Where',
        icon: 'globe-americas',
        color: 'red',
        border: 'border-red',
        bg: 'bg-red-20',
        hover: 'hover:bg-red-20',
      },
      {
        path: '/browse/when',
        name: 'When',
        icon: 'chart-line',
        color: 'red',
        border: 'border-red',
        bg: 'bg-red-20',
        hover: 'hover:bg-red-20',
      },
      {
        path: '/browse/what',
        name: 'What',
        icon: 'bars',
        color: 'red',
        border: 'border-red',
        bg: 'bg-red-20',
        hover: 'hover:bg-red-20',
      },
    ],
  },
  {
    path: '/services',
    name: 'Services',
    icon: 'cog',
    color: 'green',
    border: 'border-green',
    bg: 'bg-green-20',
    hover: 'hover:bg-green-20',
  },
  {
    path: '/about',
    name: 'About',
    icon: 'question',
    color: 'blue',
    border: 'border-blue',
    bg: 'bg-blue-20',
    hover: 'hover:bg-blue-20',
  },
];

/**
 * Front page images & texts
 */

export const frontPageImageTexts: any = {
  1: {
    title: 'The Propylea',
    text: 'This is the glorious entrance to the Acropolis of Athens and its monuments, constructed as part of the Perikles programme. The Propylaea, erected between 437 and 432 BC.'
  },
  2: {
    title: 'Ajdovščina nad Rodikom',
    text: 'Ajdovščina nad Rodikom, Late Antique hillfort (3rd−4th century), visualisation of LiDAR data. The settlement is an example of a Roman urbs quadrata town built within the ramparts of an abandoned Iron Age hillfort. A Roman-era access road is visible to the southwest, and a network of prehistoric sunken roads is visible to the east. Data processing: Edisa Lozić.'
  },
  3: {
    title: 'Acropolis of Athens',
    text: 'The Acropolis of Athens is an ancient citadel located on a rocky outcrop above the city of Athens and contains the remains of several ancient buildings of great architectural and historical significance.'
  },
  4: {
    title: 'The Cantabrian caves',
    text: 'Roof of the Cave of Altamira (replica) - National Archaeological Museum.'
  },
  5: {
    title: 'Angelokastro (Corfu)',
    text: 'Front view of Angelokastro approaching from the nearby village of Krini. Archangel Michael\'s church at the Acropolis can be seen at the top left of the castle.'
  },
  6: {
    title: 'The Baths of Constantine',
    text: 'A characteristic expression of Roman civilization, the baths were one of the most widespread public places at that time. In addition to their hygienic function, the thermal baths also had a strong social role.'
  },
  7: {
    title: 'The dolphins of Knossos',
    text: 'A detail of the dolphin fresco, the Minoan palace of Knossos, Crete, (1700-1450 BCE).'
  },
  8: {
    title: 'Dancers, Tomb of the Triclinium, Tarquinia',
    text: 'The Tomb of the Triclinium is an Etruscan tomb in the Necropolis of Monterozzi (near Tarquinia, Italy) dated to approximately 470 BC.'
  },
  9: {
    title: 'Ponte Sant\'Angelo',
    text: 'Ponte Sant\'Angelo, originally the Aelian Bridge or Pons Aelius, is a Roman bridge in Rome, Italy, completed in 134 AD by Roman Emperor Hadrian.'
  },
  10: {
    title: 'St Noe and the Ark Mosaic',
    text: 'This is the mosaics of St Noe and the Ark, located in the Monreale Cathedral. The Mosaics form the building\'s main internal feature and cover 6,500 m2.'
  },
  11: {
    title: 'The Alexander Mosaic',
    text: 'The Alexander Mosaic in The National Archaeological Museum of Naples. The museums collection includes works from Greek, Roman and Renaissance times.'
  },
  12: {
    title: 'Temple of Hercules',
    text: 'Temple of Hercules is a historic site in the Amman Citadel in Amman, Jordan. It is thought to be the most significant Roman structure in the Amman Citadel. According to an inscription the temple was built when Geminius Marcianus was governor of the Province of Arabia (AD 162-166).'
  },
  13: {
    title: 'Bison Licking Insect Bite',
    text: 'Bison Licking Insect Bite is a prehistoric carving from the Upper Paleolithic, found at Abri de la Madeleine near Tursac in Dordogne, France, the type-site of the Magdalenian culture.'
  },
  14: {
    title: 'Prehistoric Cave Painting',
    text: 'Prehistoric rupestral cave painting.'
  },
  15: {
    title: 'Rodine, Slovenia. Sedimentary cores.',
    text: '5000 years of grazing and mining activities in the Julian Alps is the heart of research concentrated on studies of the past environment, archaeological settlement patterns, and economy. Photo: Maja Andrić.'
  },
  16: {
    title: 'Jezero v Ledvicah, Julian Alps, Slovenia',
    text: 'The multidisciplinary approach integrates palaeoecological, archaeological, and geological research in order to better understand changes in the natural environment, and human adaptation to these changes. It focuses on high-altitude archaeological sites and lakes in the Julian Alps that have been inhabited since (at least) the Bronze Age. We concentrate on studies of the past environment, archaeological settlement patterns, and economy. Photo: Maja Andrič.'
  },
  17: {
    title: 'Castel Sant\'Angelo',
    text: 'The Mausoleum of Hadrian, usually known as Castel Sant\'Angelo, is a towering cylindrical building in Parco Adriano, Rome, Italy.'
  },
  18: {
    title: 'Roman Forum',
    text: 'The Roman Forum is a rectangular forum (plaza) surrounded by the ruins of several important ancient government buildings at the center of the city of Rome.'
  },
  19: {
    title: 'Filopappou Hill',
    text: 'Filopappou Hill in Athens is located to the south west of the Acropolis, inside the huge green area that also contains the “Pynx” and the “Hill of the Nymphs”. It is also know as the “Hill of the Muses” to whom it is dedicated.'
  },
  20: {
    title: 'Viktorjev spodmol, Slovenia. Excavation 2021',
    text: 'The rock shelter Viktorjev spodmol is one of the rare Mesolithic sites with Sauveterrian finds in Slovenia. It was also inhabited in the Castelnovian and later periods. Photo: Matija Turk.'
  },
  21: {
    title: 'Stare gmajne, Slovenia, view from the south-east over the pile-dwelling settlement',
    text: 'The site Stare gmajne in Ljubljansko barje was populated twice, in the late 34th century BC for the first time and in the second half of the 32nd century BC for the second time. Settlements were built on marshy ground, most probably on a lakeshore. Prehistoric wooden wheel with an axle, two logboats and  remains of a yarn were found there. Photo: Anton Velušček'
  },
  22: {
    title: 'Archaeological site of Marof',
    text: 'The archaeological site of Marof in Novo mesto consists of cemeteries Kapiteljska njiva (Late Bronze Age, Early Iron Age and Late Iron Age) and Mestne njive (Late Bronze Age), and the prehistoric fortified settlement Marof, on three summits of the hill that closes the access to the peninsula created by the Krka River. Novo mesto, Slovenia. Cemetery at Mestne njive (front), settlement at Marof Hill (right) and the Gorjanci Hills (in the horizon) with the town of Novo mesto in the middle. Photo: Borut Križ.'
  },
  23: {
    title: 'Vinji vrh, Slovenia, view from the Krka River',
    text: 'The Iron Age settlement, which encompassed most of the vast Veliki Vinji vrh, is one of the major Iron Age settlements in the Dolenjska region. Due to its dominant position, it perfectly controls the surrounding area, both near and far. In shape it has adapted itself strongly to the configuration of the terrain. The settlement on Vinji vrh is also centrally located in relation to the associated cemeteries. These are most numerous on the western ridge (barrows) and on the southern slope (flat cemeteries). Photo: Borut Križ.'
  },
  24: {
    title: 'Ocra / Razdrto, Slovenia',
    text: 'The routes between the north Adriatic and the Apennine peninsula on the west and the Balkans and the central Danubian plains on the east ran across Razdrto ever since ancient times. In the late prehistory and in the early Roman era the pass of Razdrto was known under the name of Ocra as was the mountain above it. Ocra / Razdrto, Slovenia, view from the east. Postojna basin in the foreground, the mountain Nanos to the right. Photo: Alma Bavdek.'
  },
  25: {
    title: 'Ptuj, Slovenia. View from Panorama to the Castle Hill with the old town below',
    text: 'Colonia Ulpia Traiana Poetovio was situated in the province of Pannonia Superior and developed into one of the most important Roman towns in the central Danube region. Photo: Slavko Ciglenečki.'
  },
  26: {
    title: 'Gorjanci Hills, Slovenia. The view of the slopes of Gorjanci from the north',
    text: 'The southern slopes of Gorjanci mountain chain above the village of Mihovo were continuously inhabited from the Neolithic period to the Middle Ages. The so called "Vlach Trail" winds its way down the slopes of the spurs that descend from the forested massif of the Gorjanci Hills towards the flat plain of Šentjernejsko polje, cutting deeply into the dolomite ridges. Photo: Borut Križ.'
  },
  27: {
    title: 'Ajdna, Slovenia',
    text: 'Ajdna (1048 m above sea level) is a mountain with precipitous tents on the southern slope of Mt Stol. The Late Antique hilltop settlement was built on three terraces. The central part is occupied by an Early Christian church (preserved), approx. 25 structures (5 preserved) and two water cisterns. Eleven graves have been discovered in the church, but the actual burial site is yet unknown. Photo: Slavko Ciglenečki.'
  },
  28: {
    title: 'Rifnik, Slovenia. View to the reconstructed part of the settlement with walls and towers',
    text: 'The settlement, surrounded by walls in the 1st phase (2nd half of the 4th century), had a large refuge place inside. Towers were added to the walls after the end of the 4th century. There were also several dwellings along the walls. The central part of the settlement is almost undeveloped, occupied only by a large church, a small church and a water reservoir. Photo: Slavko Ciglenečki.'
  },
  29: {
    title: 'Tonovcov grad, Slovenia, an impressive location of the settlement above the Soča River',
    text: 'Fortified hilltop settlement Tonovcov grad near Kobarid is one of the best preserved Late Antique settlements in Slovenia and the southeastern Alpine area. Investigations performed between1993 and 2005 revealed the remains of three churches, some dwelling houses and a water cistern. Photo: Slavko Ciglenečki.'
  },
  30: {
    title: 'Neanderthal flute',
    text: 'The Divje Babe I cave site, the most prominent Middle Paleolithic site in Slovenia, became famous for the discovery of what current investigations indicate could be the oldest wind instrument, made of the bone of a cave bear, discovered. Divje babe I, Slovenia. Bone wind instrument known as Neanderthal flute (60.000-50.000 BP). Kept by Narodni muzej Slovenije. Photo: Marko Zaplatil.'
  },
  31: {
    title: 'Prehistoric wooden wheel',
    text: 'A prehistoric wooden wheel with an axle was discovered at the pile-dwelling settlement Stare gmajne at the Ljubljansko barje. Analyses indicate that it was a technologically advanced product, manufactured by a prehistoric wheelwright. Considering its radiocarbon dating and the age of the settlement, where it was found, the wheel is c. 5150 years old. Stare gmajne, Slovenia. A wooden wheel and an axle (5150 BP). Kept by Mestni muzej Ljubljana. Photo: Marko Zaplatil.'
  },
  32: {
    title: 'Amber necklace',
    text: 'The middle part of an amber necklace is complemented by two beads with canaliculi drilled from the inside. These channels have a "zebra-striped" decorative effect. Novo mesto, Slovenia, barrow cemetery at Kapiteljska njiva, Grave V/35 (5th−4th centuries BC). Kept by Dolenjski muzej Novo mesto. Photo: Borut Križ.'
  },
  33: {
    title: 'Drinking set',
    text: 'A drinking set, composed of decorated pottery vessels and small vessels with upswung handles was placed in Grave IV/3 in Kandija in Novo mesto. It accompanied two bronze situlae, each of which contained an identical small pottery vessel with upswung handle. Novo mesto, Slovenia, barrow cemetery in Kandija, Grave IV/3 (5th−4th century BC). Bronze figural ornamented situla and a small cup with upswung handle. Kept by Dolenjski muzej Novo mesto. Photo: Borut Križ.'
  },
  34: {
    title: 'Kernoi',
    text: 'Kernoi are additionally ornamented with incised decoration. Three cups attached to Kernos 2 are covered with immovable lids. This suggests that only the form of the vessel was copied, but it was no longer used in a ritual. Novo mesto, Kapiteljska njiva, Grave VII/20 (6th century BC). Richly decorated pottery kernoi. Kept by Dolenjski muzej Novo mesto. Photo: Borut Križ.'
  },
  35: {
    title: 'Celtic bracelets',
    text: 'Jewellery changed in Late Iron Age female costume. The characteristic Celtic bracelets were made of profiled glass, whilst there also appear figural decorated bronze belts or belt fasteners, bracelets and rings of precious metal. Novo mesto, Slovenia. Late Iron Age cemetery at Kapiteljska njiva, various graves (3rd−2nd century BC). Kept by Dolenjski muzej Novo mesto. Photo: Borut Križ.'
  },
  36: {
    title: 'Fibulae artefacts',
    text: 'In the turbulent times in the third and fourth centuries, as many as five fortified settlements developed in one small area on the Gorjanci mountain chain. They were built on the steep ridges that already afforded good natural protection and were additionally fortified with walls, ditches and towers. The central location in the group belongs to Zidani gaber. Its position, command of the road, size, powerful stone walls bound by lime mortar and the remains of stone architecture including an Early Christian church make it one of the most important administrative, military and religious Late Antique centres in Slovenia. Zidani gaber, Slovenia. Bronze decorated flat fibulae (5th-7th century). Kept by Dolenjski muzej Novo mesto. Photo: Borut Križ.'
  },
  37: {
    title: 'Geißenklösterle',
    text: 'View of the site Geißenklösterle in the Ach river valley near the town of Blaubeuren in Baden-Württemberg, southwestern Germany. This collapsed limestone cave is one of several important sites in the region called the Swabian Jura and was inscribed onto the UNESCO World Heritage List in 2017. The site yielded finds from the Middle and Upper Paleolithic and includes the oldest mobile art objects and musical instruments found to date, dating to about 40,000 years ago. (Photo: Maria Malina, Univ. of Tübingen/ROCEEH)'
  },
  38: {
    title: 'Aghitu-3',
    text: 'Drone view of the cave site Aghitu-3 in the Vorotan river valley near the village of Aghitu in Syunik province, southern Armenia. This basalt cave yielded finds dating to the Upper Paleolithic, with further evidence of occupation during the Bronze and Iron Ages, and the Middle Classic and Medieval periods. The site yielded the oldest bone tools from Armenia, including an eyed bone needle dating to about 29,000 years ago. (Photo: Soseh Aghaian, National Academy of Sciences of Armenia/ROCEEH)'
  },
};

export const frontPageImagesTotal = Object.keys(frontPageImageTexts).length;
