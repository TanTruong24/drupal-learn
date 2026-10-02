                  HTTP REQUEST
                       |
                       v
                 Apache/Nginx
                       |
                       v
                  index.php
                       |
                       v
                Drupal Kernel
                       |
                       v
                Bootstrap Drupal
                       |
              +--------+--------+
              |                 |
              v                 v
       Site Settings     Service Container
              |                 |
              +--------+--------+
                       |
                       v
                HTTP Middleware
                       |
                       v
                  Page Cache
                       |
              +--------+--------+
              |                 |
           CACHE HIT         CACHE MISS
              |                 |
              v                 v
           Response         HTTP Kernel
                                |
                                v
                         kernel.request
                                |
                                v
                           Routing
                                |
                                v
                      Parameter Conversion
                                |
                                v
                         Access Check
                                |
                                v
                       Controller Resolver
                                |
                                v
                         kernel.controller
                                |
                                v
                            Controller
                                |
                                v
                        Business Services
                                |
                          +-----+-----+
                          |           |
                          v           v
                       Hooks        Events
                          |
                          v
                        Entity API
                          |
                          v
                        Database
                                |
                                v
                       Controller Result
                                |
                    +-----------+-----------+
                    |                       |
                    v                       v
                Render Array           HTTP Response
                    |
                    v
                 Render API
                    |
                    v
                Theme/Twig
                    |
                    v
                HTML Response
                    |
                    v
               kernel.response
                    |
                    v
                HTTP Response
                    |
                    v
                   CLIENT
----------

                ┌────────────────────┐
                │    Content Type    │
                │ Article / Property │
                └─────────┬──────────┘
                          │
                          ▼
                ┌────────────────────┐
                │       Fields       │
                │ title, image, price│
                │ category, body...  │
                └─────────┬──────────┘
                          │
              ┌───────────┴───────────┐
              ▼                       ▼
     ┌─────────────────┐     ┌─────────────────┐
     │    Taxonomy     │     │     Content     │
     │ Category/Tags   │◄────│ Node / Article  │
     └─────────────────┘     └────────┬────────┘
                                     │
                                     ▼
                            ┌──────────────────┐
                            │  Display Modes   │
                            │ Full / Teaser... │
                            └─────────┬────────┘
                                      │
                    ┌─────────────────┴──────────────┐
                    ▼                                ▼
           ┌─────────────────┐             ┌─────────────────┐
           │      Views      │             │ Entity rendering │
           │ query/filter    │             │ /node/123        │
           │ sort/list       │             │                  │
           └────────┬────────┘             └─────────────────┘
                    │
             ┌──────┴────────┐
             ▼               ▼
       Views Page        Views Block
             │               │
             ▼               ▼
           Menu          Block Layout
             │               │
             └───────┬───────┘
                     ▼
                Drupal Page
                     │
                     ▼
                Theme/Twig

---

┌─────────────────────────────────────────┐
│ 5. PRESENTATION                         │
│ Theme / Twig / Regions / Block Layout   │
├─────────────────────────────────────────┤
│ 4. QUERY & DISPLAY                      │
│ Views / View Modes                      │
├─────────────────────────────────────────┤
│ 3. CONTENT                              │
│ Nodes / Terms / Block Content           │
├─────────────────────────────────────────┤
│ 2. DATA MODEL                           │
│ Entity Types / Bundles / Fields         │
│ Content Types / Taxonomy / Block Types  │
├─────────────────────────────────────────┤
│ 1. CONFIGURATION                        │
│ Config API / YAML / cex / cim           │
└─────────────────────────────────────────┘


Entity định nghĩa "thứ gì đang tồn tại"; 

Field định nghĩa dữ liệu của nó; 

Entity Reference tạo quan hệ; 

Views tìm chúng; 

View Mode quyết định cách render; 

Block đặt output vào layout; 

Menu dẫn người dùng tới route; 

Theme/Twig tạo HTML; 

Configuration Management đưa toàn bộ cấu hình giữa các môi trường.