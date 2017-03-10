<?php

namespace Alexa\Request;

use Alexa\Utility\PurifierHelper;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class IntentRequest
 * @package Alexa\Request
 */
class IntentRequest extends Request implements RequestInterface
{
    // Traits

    use PurifierHelper;

    // Constants

    const KEY_SLOT_NAME = 'name';
    const KEY_SLOT_VALUE = 'value';

    const ERROR_INTENT_NAME_NOT_SET = 'The intent name was not set in the request';
    const ERROR_SLOTS_NOT_PRESENT = 'The slots array was not present in the request';

    // Fields

    /**
     * @var \HTMLPurifier
     */
    protected $purifier;
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $intentName;

    /**
     * @var array
     *
     * @Assert\Type("array")
     */
    protected $slots = [];

    // Hooks

    /**
     * IntentRequest()
     *
     * @param string $rawData - The original JSON response, before json_decode
     * @param string $applicationId - Your Alexa Dev Portal application ID
     * @param Certificate|null $certificate - Override the auto-generated Certificate with your own
     * @param Application|null $application - Override the auto-generated Application with your own
     * @param \HTMLPurifier|null $purifier
     *
     * @throws \InvalidArgumentException - If the intent name or slots array is not present in the request
     */
    public function __construct(
        $rawData,
        $applicationId,
        Certificate $certificate = null,
        Application $application = null,
        \HTMLPurifier $purifier = null
    ) {
        // Parent construct
        parent::__construct($rawData, $applicationId, $certificate, $application, $purifier);

        // Require intent name
        if (!isset($this->data['request']['intent']['name'])) {
            throw new \InvalidArgumentException(self::ERROR_INTENT_NAME_NOT_SET);
        }

        // Set intent name
        $this->setIntentName($this->data['request']['intent']['name']);

        // Generate $this->slots
        $this->generateSlotData();
    }

    // Public Methods

    /**
     * getSlot()
     *
     * Returns the value for the requested intent slot, or $default if not
     * found
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getSlot($name, $default = null)
    {
        if (array_key_exists($name, $this->slots)) {
                return $this->slots[$name];
        }

        return $default;
    }

    // Protected Methods

    /**
     * generateSlotData()
     *
     * Iterate $this->data, attaching slot data to $this->slots[]
     *
     * @return void
     */
    protected function generateSlotData()
    {
        // Short-circuit on null
        if (!isset($this->data['request']['intent']['slots'])) {
            throw new \InvalidArgumentException(self::ERROR_SLOTS_NOT_PRESENT);
        }

        // Iterate the slots, attaching each
        foreach ($this->data['request']['intent']['slots'] as $slotDefinition) {
            $this->attachSlot($slotDefinition);
        }
    }

    /**
     * attachSlot()
     *
     * Attach the data from the slot to $this->slots[$slotDefinition[self::KEY_SLOT_NAME]
     *
     * @param array $slotDefinition
     *
     * @return void
     */
    protected function attachSlot(array $slotDefinition)
    {
        if (isset($slotDefinition[self::KEY_SLOT_VALUE])) {
            $slotKey = $this->purifier->purify($slotDefinition[self::KEY_SLOT_NAME]);
            $slotValue = $this->purifier->purify($slotDefinition[self::KEY_SLOT_VALUE]);

            $this->slots[$slotKey] = $slotValue;
        }
    }

    // Accessors

    /**
     * @return string
     */
    public function getIntentName()
    {
        return $this->intentName;
    }

    /**
     * @return array
     */
    public function getSlots()
    {
        return $this->slots;
    }

    // Mutators

    /**
     * @param string $intentName
     */
    public function setIntentName($intentName)
    {
        $this->intentName = $this->purifier->purify((string)$intentName);
    }

    /**
     * @param array $slots
     */
    public function setSlots(array $slots)
    {
        $this->slots = $slots;
    }
}
