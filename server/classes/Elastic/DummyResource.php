<?php

namespace Elastic;

class DummyResource {

  /**
   * NOTICE
   * Properties id, nearby, similar, collection and partof
   * are NOT part of the default mapped record.
   * These fields are programatically collected and added to the
   * resource runtime by Query.getRecord()
   */

  public static function getDummy() {

    return [

      "accessPolicy" => "http://archaeologydataservice.ac.uk\/advice\/termsOfUseAndAccess",
      "accessRights" => "http://archaeologydataservice.ac.uk\/advice\/termsOfUseAndAccess",
      "ariadneSubject" => [
        [
          "prefLabel" => "Fieldwork"
        ]
      ],

      "contributor" => [
        [
          "email" => "test@test.se",
          "homepage" => "https://www.test.se",
          "institution" => "Institution of Test",
          "name" => "South Yorkshire Archaeological Services",
          "agentIdentifier" => "https://ariadne-infrastructure.eu\/aocat\/Agent\/South%20Yorkshire%20Archaeological%20Services"
        ]
      ],

      "creator" => [
        [
          "email" => "test@test.se",
          "homepage" => "https://www.test.se",
          "institution" => "Institution of Test",
          "name" => "South Yorkshire Archaeological Services",
          "agentIdentifier" => "https://ariadne-infrastructure.eu\/aocat\/Agent\/South%20Yorkshire%20Archaeological%20Services"
        ]
      ],

      "derivedSubject" => [
        [
          "prefLabel" => "vicarages",
          "source" => "Getty AAT",
          "id" => "http://vocab.getty.edu\/aat\/300005650",
          "lang" => "en"
        ]
      ],

      "language" => "fr",

      "title" => [
        [
          "text" => "En Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
          "language" => "en"
        ],
        [
          "text" => "Fr Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
          "language" => "fr"
        ],
        [
          "text" => "Sv Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
          "language" => "sv"
        ],
      ],

      "description" => [
        [
          "text" => "En Pellentesque et fringilla magna, et imperdiet nibh. Integer vitae est leo. Vivamus placerat eleifend nulla non tempor. Nunc a diam non justo feugiat blandit. Proin ut posuere magna. Nullam scelerisque at odio et scelerisque. Maecenas eget ligula sodales, ultrices massa iaculis, dapibus nisl.",
          "language" => "en"
        ],
        [
          "text" => "Da Pellentesque et fringilla magna, et imperdiet nibh. Integer vitae est leo. Vivamus placerat eleifend nulla non tempor. Nunc a diam non justo feugiat blandit. Proin ut posuere magna. Nullam scelerisque at odio et scelerisque. Maecenas eget ligula sodales, ultrices massa iaculis, dapibus nisl.",
          "language" => "da"
        ],
        [
          "text" => "Sv Pellentesque et fringilla magna, et imperdiet nibh. Integer vitae est leo. Vivamus placerat eleifend nulla non tempor. Nunc a diam non justo feugiat blandit. Proin ut posuere magna. Nullam scelerisque at odio et scelerisque. Maecenas eget ligula sodales, ultrices massa iaculis, dapibus nisl.",
          "language" => "sv"
        ],
        [
          "text" => "Es Pellentesque et fringilla magna, et imperdiet nibh. Integer vitae est leo. Vivamus placerat eleifend nulla non tempor. Nunc a diam non justo feugiat blandit. Proin ut posuere magna. Nullam scelerisque at odio et scelerisque. Maecenas eget ligula sodales, ultrices massa iaculis, dapibus nisl.",
          "language" => "es"
        ],
        [
          "text" => "Fr Pellentesque et fringilla magna, et imperdiet nibh. Integer vitae est leo. Vivamus placerat eleifend nulla non tempor. Nunc a diam non justo feugiat blandit. Proin ut posuere magna. Nullam scelerisque at odio et scelerisque. Maecenas eget ligula sodales, ultrices massa iaculis, dapibus nisl.",
          "language" => "fr"
        ],

      ],

      "digitalImage" => [
        [
          "ariadneUri" => "https://via.placeholder.com\/500\/0000FF\/FFFFFF",
          "primary" => "true",
          "providerUri" => "https://via.placeholder.com\/500\/FF0000\/FFFFFF"
        ],
        [
          "ariadneUri" => "https://www.metaldetektorfund.dk\/fundbilleder\/608\/6082d81a2d1e5.jpg",
          "primary" => "true",
          "providerUri" => "https://www.metaldetektorfund.dk\/fundbilleder\/608\/6082d8232ee7e.jpg"
        ],
        [
          "ariadneUri" => "https://via.placeholder.com\/500\/008000C\/FFFFFF",
          "primary" => "true",
          "providerUri" => "https://via.placeholder.com\/500\/eab676\/000000"
        ],
        [
          "ariadneUri" => "https://via.placeholder.com\/500\/1e81b0\/FFFFFF",
          "primary" => "true",
          "providerUri" => "https://via.placeholder.com\/500\/abdbe3\/000000"
        ]
      ],

      "has_type" => [
        "label" => "Dataset Collection",
        "uri" => "https://ariadne-infrastructure.eu\/aocat\/Concept\/AO_Type\/collection"
      ],

      "identifier" => "https://ariadne-infrastructure.eu\/aocat\/Resource\/DCDF395A-2CC8-3FC2-B9A5-5A924090DF10",

      "isPartOf" => [
          "https://ariadne-infrastructure.eu\/aocat\/Collection\/ADS\/F31C1473-3527-3C56-9801-F833D3C26A70"
      ],

      "is_about" => [
        [
          "label" => "Excavation folyamatban_2019_12_373",
          "uri" => "https://ariadne-infrastructure.eu\/aocat\/Event\/HNM\/folyamatban_2019_12_373"
        ],
        [
          "label" => "Site folyamatban_2019_12_373",
          "uri" => "https://ariadne-infrastructure.eu\/aocat\/Object\/HNM\/FA504C36-5DB3-34A2-BC6B-0A5ABBF19F83"
        ]
      ],

      "issued" => "2002-06-20",

      "landingPage" => "https://archaeologydataservice.ac.uk\/archsearch\/record?titleId=63372",

      "modified" => "2002-06-20",

      "nativePeriod" => [
        [
          "periodName" => "Medieval"
        ],
        [
          "periodName" => "Post Medieval"
        ]
      ],

      "nativeSubject" => [
        [
          "prefLabel" => "Evaluation",
          "rdfAbout" => "https://ariadne-infrastructure.eu\/aocat\/Concept\/ADS\/EVALUATION"
        ],
        [
          "prefLabel" => "Priests House",
          "rdfAbout" => "https://ariadne-infrastructure.eu\/aocat\/Concept\/ADS\/PRIESTS%20HOUSE"
        ],
        [
          "prefLabel" => "Vicarage",
          "rdfAbout" => "https://ariadne-infrastructure.eu\/aocat\/Concept\/ADS\/VICARAGE"
        ]
      ],

      "originalId" => "63372",

      "owner" => [
        [
          "email" => "test@test.se",
          "homepage" => "https://www.test.se",
          "institution" => "Institution of Test",
          "name" => "Historic England",
          "agentIdentifier" => "https://ariadne-infrastructure.eu\/aocat\/Agent\/Historic%20England"
        ]
      ],

      "publisher" => [
        [
          "email" => "test@test.se",
          "homepage" => "https://www.test.se",
          "institution" => "Institution of Test",
          "name" => "Archaeology Data Service",
          "agentIdentifier" => "https://ariadne-infrastructure.eu\/aocat\/Agent\/Archaeology%20Data%20Service"
        ]
      ],

      "resourceType" => "dataset",

      "responsible" => [
        [
          "email" => "test@test.se",
          "homepage" => "https://www.test.se",
          "institution" => "Institution of Test",
          "name" => "Historic England",
          "agentIdentifier" => "https://ariadne-infrastructure.eu\/aocat\/Agent\/Historic%20England"
        ]
      ],

      "spatial" => [
        [
          "placeName" => "CHURCH HILL 1"
        ],
        [
          "placeName" => "CHURCH HILL 2"
        ],
        [
          "placeName" => "London City"
        ],
        [
          "geopoint" => [
            "lat" => 51.5,
            "lon" => -0.12
          ]
        ],
        [
          "address" => "Test Address 22"
        ],
        [
          "polygon" => "POLYGON ((16.1852587025660000 58.6269058797496000, 16.1800703481449000 58.6252377443833000, 16.1771682694413000 58.6250241163845000, 16.1735657399840000 58.6255031471420000, 16.1712630134840000 58.6294362237405000, 16.1704178163986000 58.6317088087769000, 16.1702258150040000 58.6338381814251000, 16.1705463618886000 58.6355510927345000, 16.1709315883892000 58.6372290978782000, 16.1721871797732000 58.6393452908180000, 16.1742983679656000 58.6414536667355000, 16.1777860342630000 58.6453338779081000, 16.1812934456178000 58.6439290680244000, 16.1800579712174000 58.6424304748620000, 16.1831794711219000 58.6413036817908000, 16.1871835530808000 58.6390362238355000, 16.1871358170521000 58.6376296829223000, 16.1887718946604000 58.6354183133365000, 16.1882519269375000 58.6336729674983000, 16.1857479946616000 58.6316370694548000, 16.1850506435553000 58.6304767286314000, 16.1847743086844000 58.6281457414074000, 16.1852587025660000 58.6269058797496000))"
        ],
        [
          "boundingbox" => "polygon ((0.7591929338 51.2044631369, 0.7591929338 51.201928315, 0.763566146 51.201928315, 0.763566146 51.2044631369, 0.7591929338 51.2044631369))"
        ],
        [
          "coordinatePrecision" => "999"
        ],
        [
          "spatialPrecision" => "999"
        ]
      ],

      "temporal" => [
        [
          "from" => "1066",
          "periodName" => "Medieval",
          "until" => "1540",
          "uri" => "https://ariadne-infrastructure.eu\/aocat\/Time-Span\/MEDIEVAL"
        ],
        [
          "from" => "1540",
          "periodName" => "Post Medieval",
          "until" => "1901",
          "uri" => "https://ariadne-infrastructure.eu\/aocat\/Time-Span\/POST%20MEDIEVAL"
        ]
      ],

      "wasCreated" => "2021",

      "id" => "dummyRecord",

      "similar" => [
        [
          "id" => "3C3C7A86-FF09-3431-95B1-B9A4AA8293AF",
          "type" => [
            [
              "prefLabel" => "Site\/monument"
            ]
          ],
          "title" => [ 
            "text" => "1 Similar Title",
            "language" => "en",
          ]
        ],
        [
          "id" => "90D1C95D-E249-3E74-92D9-B58FDF690CC7",
          "type" => [
            [
              "prefLabel" => "Fieldwork archive"
            ]
          ],
          "title" => [ 
            "text" => "2 Similar Title",
            "language" => "en",
          ]

        ]
      ],

      "nearby" => [
        [
          "placeName" => "Nearby Place 1",
          "title" => [ 
            "text" => "1 Nearby Resource",
            "language" => "en",
          ],
          "id" => "CA076E46-5CED-322C-B77E-3B90C11B968B",
          "geopoint" => [
            "lat" => 51.5,
            "lon" => 0.14
          ]
        ],
        [
          "placeName" => "Nearby Place 2",
          "title" => [ 
            "text" => "2 Nearby Resource",
            "language" => "en",
          ],
          "id" => "CA076E46-5CED-322C-B77E-3B90C11B968B",
          "geopoint" => [
            "lat" => 51.5,
            "lon" => 0.18
          ]
        ],
        [
          "placeName" => "Nearby Place 3",
          "title" => [ 
            "text" => "3 Nearby Resource",
            "language" => "en",
          ],
          "id" => "CA076E46-5CED-322C-B77E-3B90C11B968B",
          "geopoint" => [
            "lat" => 51.5,
            "lon" => 0.22
          ]
        ]
      ],

      "partof" => [
        [
          "id" => "57DB9A00-5856-3120-BE2A-2FFBD253CA04",
          "title" => [
            "text" => "Lille ligearmet fibula",
            "language" => "en",
          ]
        ],
        [
          "id" => "57DB9A00-5856-3120-BE2A-2FFBD253CA04",
          "title" => [ 
            "text" => "Lille ligearmet fibula",
            "language" => "en",
          ]
        ]
      ],

      "collection" => [
        "total" => 100,
        "hits" => [
          [
            "id" => "EF681541-B1E7-3B5C-9690-5AAC5F026102",
            "title" => [
              "text" => "Lille ligearmet fibula",
              "language" => "en",
            ]
          ],
          [
            "id" => "9F3A4D45-1EDE-3461-9324-2E67D041BC61",
            "title" => [
              "text" => "Lille ligearmet fibula",
              "language" => "en",
            ]
          ],
          [
            "id" => "4560250B-886A-3A5B-8471-FC2E85ECBFF8",
            "title" => [
              "text" => "Lille ligearmet fibula",
              "language" => "en",
            ]
          ],
          [
            "id" => "5DB8EAE8-546B-38BC-99D1-6F2AD25A5934",
            "title" => [
              "text" => "Lille ligearmet fibula",
              "language" => "en",
            ]
          ]
        ]
      ],

      "isAboutResource" => [
        [
          "id" => "D1D18994-96D2-38D5-A725-6060D5A789B9",
          "title" => [
            "text" => "Tilly's Lane, Staines, Surrey, interim assessment report=> archaeological investigations",
            "language" => "en"
          ]
        ],
        [
          "id" => "D69B0576-3BC9-3FE2-ABC1-C29DFC376EC4",
          "title" => [
            "text" => "A259 Bexhill and Hastings Western and A259 Hastings Eastern Bypass. Archaeological Trial Trenching Evaluation",
            "language" => "se"
          ]
        ]
      ],

    ];

  }

}