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