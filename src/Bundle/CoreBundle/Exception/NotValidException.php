<?php


namespace UniteCMS\CoreBundle\Exception;


use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Throwable;

class NotValidException extends ValidatorException
{
    /**
     * @var ConstraintViolationListInterface $violationList
     */
    protected $violationList;

    public function __construct(ConstraintViolationListInterface $violationList, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->violationList = $violationList;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Return all violations in this exception.
     *
     * @return ConstraintViolationListInterface
     */
    public function getViolationList(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }

    /**
     * Map all violations in this exception to the given form.
     *
     * @param FormInterface $form
     * @return FormInterface
     */
    public function mapToForm(FormInterface $form) : FormInterface {
        $violationMapper = new ViolationMapper();
        foreach ($this->getViolationList() as $violation) {
            $violationMapper->mapViolation($violation, $form);
        }
        return $form;
    }
}
