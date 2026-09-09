<?php

declare(strict_types=1);

namespace CoolMS\Field\Entity;

use CoolMS\Entity\FieldDefinitionInterface;
use CoolMS\Entity\OrderableInterface;

interface DefinitionInterface extends FieldDefinitionInterface, OrderableInterface
{
    /**
     * Entity alias to which the field is bound (e.g., 'product', 'customer').
     */
    public string $entityAlias {
        get;
    }

    /**
     * Additional configuration options (label, help text, default value, options for select).
     *
     * @var array<string, mixed>
     */
    public array $options {
        get;
    }

    /**
     * Raw serializer configuration array.
     *
     * @var array<string, mixed>
     */
    public array $serializerConfig {
        get;
    }

    /**
     * Serialized property name override (from serializerConfig).
     */
    public ?string $serializedName {
        get;
    }

    /**
     * Whether this field should be excluded from (de)normalization.
     */
    public bool $ignore {
        get;
    }

    /**
     * Raw form configuration array.
     *
     * @var array<string, mixed>
     */
    public array $formConfig {
        get;
    }

    /**
     * Symfony Form type class-string (from formConfig).
     */
    public ?string $formType {
        get;
    }

    /**
     * Symfony Form options array (from formConfig).
     *
     * @var array<string, mixed>
     */
    public array $formOptions {
        get;
    }

    /**
     * Whether this field should appear in forms (from formConfig).
     */
    public bool $showInForm {
        get;
    }

    /**
     * Raw HTTP-API configuration array, read by whatever exposes the
     * entity over HTTP.
     *
     * @var array<string, mixed>
     */
    public array $apiConfig {
        get;
    }

    /**
     * Whether this field is filterable over the HTTP API (from apiConfig).
     */
    public bool $isFilterable {
        get;
    }

    /**
     * Whether this field is sortable over the HTTP API (from apiConfig).
     */
    public bool $isSortable {
        get;
    }

    /**
     * Whether this field is full-text searchable over the HTTP API
     * (from apiConfig).
     */
    public bool $isSearchable {
        get;
    }

    /**
     * Human-readable label (falls back to ucfirst(name)); settable in this module.
     */
    public string $label {
        get;
        set;
    }
}
