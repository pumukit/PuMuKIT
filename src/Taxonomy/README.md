# Taxonomy Bounded Context

Este es el Bounded Context de **Taxonomy** que gestiona Tags y Categories del sistema PuMuKIT.

## Estructura del Contexto

```
Taxonomy/
├── Domain/
│   ├── Tag.php                                 # Entidad de dominio
│   ├── Repository/
│   │   └── TagRepositoryInterface.php          # Interface del repositorio
│   └── Exception/
│       ├── TagNotFoundException.php            # Excepción cuando no se encuentra
│       └── TagAlreadyExistsException.php       # Excepción cuando ya existe
├── Application/
│   ├── CreateTag/
│   │   ├── CreateTagRequest.php                # DTO de entrada
│   │   ├── CreateTagResponse.php               # DTO de salida
│   │   └── CreateTagService.php                # Caso de uso
│   ├── UpdateTag/
│   │   ├── UpdateTagRequest.php
│   │   ├── UpdateTagResponse.php
│   │   └── UpdateTagService.php
│   ├── ViewTag/
│   │   ├── ViewTagRequest.php
│   │   ├── ViewTagResponse.php
│   │   └── ViewTagService.php
│   ├── ListTags/
│   │   ├── ListTagsRequest.php
│   │   ├── ListTagsResponse.php
│   │   └── ListTagsService.php
│   └── DeleteTag/
│       ├── DeleteTagRequest.php
│       └── DeleteTagService.php
├── Infrastructure/
│   └── Persistence/
│       └── DoctrineTagRepository.php           # Implementación con Doctrine ODM
└── UI/
    └── Controller/
        ├── CreateTagController.php             # POST /api/tags
        ├── ViewTagController.php               # GET /api/tags/{id}
        ├── ListTagsController.php              # GET /api/tags
        ├── UpdateTagController.php             # PUT /api/tags/{id}
        └── DeleteTagController.php             # DELETE /api/tags/{id}
```

## Arquitectura Hexagonal

Este contexto sigue los principios de **Arquitectura Hexagonal**:

### Domain Layer (Núcleo)
- **Tag**: Entidad de dominio con toda la lógica de negocio
- **TagRepositoryInterface**: Puerto de salida para persistencia
- **Excepciones**: Excepciones específicas del dominio

### Application Layer (Casos de Uso)
- **Services**: Orquestan la lógica de aplicación
- **Request/Response DTOs**: Entrada y salida de cada caso de uso

### Infrastructure Layer (Adaptadores)
- **DoctrineTagRepository**: Adaptador para MongoDB usando Legacy Entity
- Traduce entre la entidad de dominio (Tag) y la entidad Legacy (Pumukit\SchemaBundle\Document\Tag)

### UI Layer (Interfaz)
- **Controllers**: API REST expuesta para interactuar con el contexto

## API Endpoints

### Crear Tag
```bash
POST /api/tags
Content-Type: application/json

{
  "cod": "PUCHD",
  "title": {
    "en": "PuMuKIT HD",
    "es": "PuMuKIT HD"
  },
  "label": {
    "en": "HD Channel"
  },
  "description": {
    "en": "High Definition Channel"
  },
  "slug": "pumukit-hd",
  "metatag": false,
  "display": true,
  "parent_id": null,
  "properties": {}
}
```

### Ver Tag
```bash
GET /api/tags/{id}
```

### Listar Tags
```bash
# Todos los tags
GET /api/tags

# Solo tags raíz
GET /api/tags?only_roots=true

# Tags hijos de un parent específico
GET /api/tags?parent_id={parentId}
```

### Actualizar Tag
```bash
PUT /api/tags/{id}
Content-Type: application/json

{
  "title": {
    "en": "Updated Title"
  },
  "label": {
    "en": "Updated Label"
  },
  "description": {
    "en": "Updated Description"
  },
  "slug": "updated-slug",
  "metatag": true,
  "display": true,
  "properties": {}
}
```

### Eliminar Tag
```bash
DELETE /api/tags/{id}
```

## Casos de Uso

### CreateTag
Crea un nuevo tag en el sistema.

**Validaciones:**
- El `cod` no debe existir
- Si se especifica `parent_id`, el parent debe existir

### UpdateTag
Actualiza un tag existente.

**Validaciones:**
- El tag debe existir
- El `cod` no se puede modificar

### ViewTag
Obtiene los detalles de un tag.

**Validaciones:**
- El tag debe existir

### ListTags
Lista tags según filtros.

**Opciones:**
- `only_roots=true`: Solo tags raíz (sin parent)
- `parent_id={id}`: Solo hijos de un parent específico
- Sin filtros: Todos los tags

### DeleteTag
Elimina un tag del sistema.

**Validaciones:**
- El tag debe existir

## Testing

### Ejecutar Tests Unitarios
```bash
php bin/phpunit tests/Taxonomy/
```

### Tests Disponibles
- `CreateTagServiceTest`: Tests del caso de uso CreateTag
- `ViewTagServiceTest`: Tests del caso de uso ViewTag
- (Más tests por implementar)

## Migración desde Legacy

Este contexto utiliza **Strangler Pattern** para migrar progresivamente desde la entidad Legacy:

1. **Domain**: Nueva entidad `Tag` con lógica de negocio
2. **Repository**: `DoctrineTagRepository` traduce entre Domain y Legacy
3. **Coexistencia**: El código Legacy sigue funcionando mientras se migra

### Ventajas
- ✅ Migración progresiva sin Big Bang
- ✅ Tests independientes de Doctrine
- ✅ Lógica de negocio en el dominio
- ✅ Fácil cambiar de persistencia en el futuro

## Próximos Pasos

1. ✅ **CRUD de Tag** (completado)
2. ⏳ **CRUD de Category** (pendiente)
3. ⏳ **Relaciones Tag-MultimediaObject** (pendiente)
4. ⏳ **Relaciones Category-Series** (pendiente)
5. ⏳ **Tests de integración** (pendiente)

## Contribuir

Para añadir nuevos casos de uso:

1. Crear Request/Response DTOs en `Application/{CasoDeUso}/`
2. Crear Service en `Application/{CasoDeUso}/{CasoDeUso}Service.php`
3. Crear Controller en `UI/Controller/{CasoDeUso}Controller.php`
4. Crear Tests en `tests/Taxonomy/Application/{CasoDeUso}/`

## Notas

- **i18n**: Los campos `title`, `label` y `description` son multiidioma (array con locale como clave)
- **Tree Structure**: Tags tienen estructura jerárquica con `parent` y `children`
- **Properties**: Campo genérico para metadata adicional
- **Materialized Path**: Gedmo Tree gestiona automáticamente el `path` y `level`

