<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Exception\WrongType;

class Report
{
    const TYPE_UNKNOWN = 1;
    const TYPE_INFO = 2;
    const TYPE_WARNING = 4;
    const TYPE_FATAL = 8;

    private $format = '';
    private $args = [];
    /** @var IReportContext $context */
    private $context;
    private $type = self::TYPE_UNKNOWN;

    public function __construct(int $type, string $format, IReportContext $context = null, ?string ...$args)
    {
        $this->setType($type);
        $this->setFormat($format);
        $this->setArgs(...$args);
        $this->setContext($context);
    }

    /**
     * Sets the severity type of the report.
     *
     * @param integer $type The severity of the report.
     * @return void
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the severity type of the report.
     *
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Sets the sprintf format of the message.
     *
     * @param string $format The message format.
     * @return void
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * Returns the sprintf format of the message.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Sets the additional arguments to fill into the message.
     *
     * @param string ...$args Any arguments to use in the format.
     * @return void
     */
    public function setArgs(?string ...$args): void
    {
        $this->args = $args;
    }

    /**
     * Returns the additional arguments that are filled into the message.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Sets the context of the report.
     *
     * @param IReportContext $context The context of the report.
     * @return void
     */
    public function setContext(IReportContext $context = null): void
    {
        $this->context = $context;
    }

    /**
     * Returns the context of the report. This will typically be the data the report concerns
     * and can be used for display.
     *
     * @return IReportContext|null
     */
    public function getContext(): ?IReportContext
    {
        return $this->context;
    }

    /**
     * Sets the context of the report as Json.
     *
     * @param Json $json The Json.
     * @return void
     */
    public function setJson(Json $json = null): void
    {
        if ($json !== null && !($json instanceof IReportContext)) {

            throw new WrongType('Json must be report context');
        }

        $this->context = $json;
    }

    /**
     * Returns the context as Json.
     *
     * @return Json
     */
    public function getJson(): ?Json
    {
        if ($this->context !== null && !($this->context instanceof Json)) {

            throw new WrongType('Context must be Json');
        }

        return $this->context;
    }

    /**
     * Returns the completed message after inserting arguments.
     *
     * @return string
     */
    public function buildMessage(): string
    {
        return sprintf($this->format, ...$this->args);
    }

    /**
     * Creates an info-type report.
     *
     * @param string $format The message format.
     * @param string ...$args Any arguments to use in the format.
     * @return self
     */
    public static function info(string $format, string ...$args): self
    {
        return self::createReport(self::TYPE_INFO, $format, ...$args);
    }

    /**
     * Creates a warning-type report.
     *
     * @param string $format The message format.
     * @param string ...$args Any arguments to use in the format.
     * @return self
     */
    public static function warning(string $format, string ...$args): self
    {
        return self::createReport(self::TYPE_WARNING, $format, ...$args);
    }

    /**
     * Creates a fatal-type report.
     *
     * @param string $format The message format.
     * @param string ...$args Any arguments to use in the format.
     * @return self
     */
    public static function fatal(string $format, ?string ...$args): self
    {
        return self::createReport(self::TYPE_FATAL, $format, ...$args);
    }

    /**
     * Creates and returns a brand new report with the provided data.
     *
     * @param integer $type The severity of the report.
     * @param string $format The message format.
     * @param string ...$args Any arguments to use in the format.
     * @return void
     */
    protected static function createReport(int $type, string $format, ?string ...$args): self
    {
        return new static($type, $format, null, ...$args);
    }

    /**
     * Creates a standardized string from a key that is expected
     * to have potential HTML characters. Multiple keys are
     * separated by a comma.
     *
     * @param string ...$values The keys to sanitize and surround with quotes.
     * @return string
     */
    public static function key(?string ...$keys): string
    {
        $buffer = '';

        for ($i = 0, $j = count($keys); $i < $j; $i++) {

            $buffer .= '"' . htmlentities($keys[$i]) . '"';

            if ($i + 1 < $j) {

                $buffer .= ', ';
            }
        }

        return $buffer;
    }

    /**
     * Creates a standardized string from a value that is expected
     * to have potential HTML characters. Multiple values are
     * separated by a comma.
     *
     * @param string ...$values The values to sanitize and surround with code blocks.
     * @return string
     */
    public static function value(?string ...$values): string
    {
        $buffer = '';

        for ($i = 0, $j = count($values); $i < $j; $i++) {

            $buffer .= '<code>' . htmlentities($values[$i]) . '</code>';

            if ($i + 1 < $j) {

                $buffer .= ', ';
            }
        }

        return $buffer;
    }
}