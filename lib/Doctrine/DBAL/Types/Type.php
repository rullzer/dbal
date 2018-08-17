<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\DBALException;
use function end;
use function explode;
use function get_class;
use function str_replace;

/**
 * The base class for so-called Doctrine mapping types.
 *
 * A Type object is obtained by calling the static {@link getType()} method.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since  2.0
 */
abstract class Type
{
    const TARRAY = 'array';
    const SIMPLE_ARRAY = 'simple_array';
    const JSON_ARRAY = 'json_array';
    const JSON = 'json';
    const BIGINT = 'bigint';
    const BOOLEAN = 'boolean';
    const DATETIME = 'datetime';
    const DATETIME_IMMUTABLE = 'datetime_immutable';
    const DATETIMETZ = 'datetimetz';
    const DATETIMETZ_IMMUTABLE = 'datetimetz_immutable';
    const DATE = 'date';
    const DATE_IMMUTABLE = 'date_immutable';
    const TIME = 'time';
    const TIME_IMMUTABLE = 'time_immutable';
    const DECIMAL = 'decimal';
    const INTEGER = 'integer';
    const OBJECT = 'object';
    const SMALLINT = 'smallint';
    const STRING = 'string';
    const TEXT = 'text';
    const BINARY = 'binary';
    const BLOB = 'blob';
    const FLOAT = 'float';
    const GUID = 'guid';
    const DATEINTERVAL = 'dateinterval';

    /**
     * Map of already instantiated type objects. One instance per type (flyweight).
     *
     * @var array
     */
    private static $_typeObjects = [];

    /**
     * The map of supported doctrine mapping types.
     *
     * @var array
     */
    private static $_typesMap = [
        self::TARRAY => ArrayType::class,
        self::SIMPLE_ARRAY => SimpleArrayType::class,
        self::JSON_ARRAY => JsonArrayType::class,
        self::JSON => JsonType::class,
        self::OBJECT => ObjectType::class,
        self::BOOLEAN => BooleanType::class,
        self::INTEGER => IntegerType::class,
        self::SMALLINT => SmallIntType::class,
        self::BIGINT => BigIntType::class,
        self::STRING => StringType::class,
        self::TEXT => TextType::class,
        self::DATETIME => DateTimeType::class,
        self::DATETIME_IMMUTABLE => DateTimeImmutableType::class,
        self::DATETIMETZ => DateTimeTzType::class,
        self::DATETIMETZ_IMMUTABLE => DateTimeTzImmutableType::class,
        self::DATE => DateType::class,
        self::DATE_IMMUTABLE => DateImmutableType::class,
        self::TIME => TimeType::class,
        self::TIME_IMMUTABLE => TimeImmutableType::class,
        self::DECIMAL => DecimalType::class,
        self::FLOAT => FloatType::class,
        self::BINARY => BinaryType::class,
        self::BLOB => BlobType::class,
        self::GUID => GuidType::class,
        self::DATEINTERVAL => DateIntervalType::class,
    ];

    /**
     * Prevents instantiation and forces use of the factory method.
     */
    final private function __construct()
    {
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Gets the default length of this type.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return int|null
     *
     * @todo Needed?
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return null;
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array                                     $fieldDeclaration The field declaration.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform         The currently used database platform.
     *
     * @return string
     */
    abstract public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform);

    /**
     * Gets the name of this type.
     *
     * @return string
     *
     * @todo Needed?
     */
    abstract public function getName();

    /**
     * Factory method to create type instances.
     * Type instances are implemented as flyweights.
     *
     * @param string $name The name of the type (as returned by getName()).
     *
     * @return \Doctrine\DBAL\Types\Type
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getType($name)
    {
        if ( ! isset(self::$_typeObjects[$name])) {
            if ( ! isset(self::$_typesMap[$name])) {
                throw DBALException::unknownColumnType($name);
            }
            self::$_typeObjects[$name] = new self::$_typesMap[$name]();
        }

        return self::$_typeObjects[$name];
    }

    /**
     * Adds a custom type to the type map.
     *
     * @param string $name      The name of the type. This should correspond to what getName() returns.
     * @param string $className The class name of the custom type.
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function addType($name, $className)
    {
        if (isset(self::$_typesMap[$name])) {
            throw DBALException::typeExists($name);
        }

        self::$_typesMap[$name] = $className;
    }

    /**
     * Checks if exists support for a type.
     *
     * @param string $name The name of the type.
     *
     * @return bool TRUE if type is supported; FALSE otherwise.
     */
    public static function hasType($name)
    {
        return isset(self::$_typesMap[$name]);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @param string $name
     * @param string $className
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function overrideType($name, $className)
    {
        if ( ! isset(self::$_typesMap[$name])) {
            throw DBALException::typeNotFound($name);
        }

        if (isset(self::$_typeObjects[$name])) {
            unset(self::$_typeObjects[$name]);
        }

        self::$_typesMap[$name] = $className;
    }

    /**
     * Gets the (preferred) binding type for values of this type that
     * can be used when binding parameters to prepared statements.
     *
     * This method should return one of the {@link \Doctrine\DBAL\ParameterType} constants.
     *
     * @return int
     */
    public function getBindingType()
    {
        return ParameterType::STRING;
    }

    /**
     * Gets the types array map which holds all registered types and the corresponding
     * type class
     *
     * @return array
     */
    public static function getTypesMap()
    {
        return self::$_typesMap;
    }

    /**
     * Does working with this column require SQL conversion functions?
     *
     * This is a metadata function that is required for example in the ORM.
     * Usage of {@link convertToDatabaseValueSQL} and
     * {@link convertToPHPValueSQL} works for any type and mostly
     * does nothing. This method can additionally be used for optimization purposes.
     *
     * @return bool
     */
    public function canRequireSQLConversion()
    {
        return false;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a database value.
     *
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return $sqlExpr;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a PHP value.
     *
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return $sqlExpr;
    }

    /**
     * Gets an array of database types that map to this Doctrine type.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return array
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return [];
    }

    /**
     * If this Doctrine Type maps to an already mapped database type,
     * reverse schema engineering can't tell them apart. You need to mark
     * one of those types as commented, which will have Doctrine use an SQL
     * comment to typehint the actual Doctrine Type.
     *
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return false;
    }
}
