<?php

namespace App\Request;

use Symfony\Component\Validator\{Constraints as Assert, Validation};
use Symfony\Component\HttpFoundation\{Request, Exception\BadRequestException};
use App\Exception\ValidationException;

class CreditCardUpdateRequest
{
    private ?string $name = null;
    private ?string $description = null;
    private ?float $annualCharges = null;

    public function __construct(Request $request)
    {
        $data = $request->request->all();

        if (!isset($data['credit_card_edit']) || !is_array($data['credit_card_edit'])) {
            throw new BadRequestException('Invalid request data format');
        }

        $formData = $data['credit_card_edit'];
        $this->name = $formData['name'] ?? null;
        $this->description = $formData['description'] ?? null;
        $this->annualCharges = isset($formData['annualCharges']) ? (float) $formData['annualCharges'] : null;
    }

    public function validate(): void
    {
        $validator = Validation::createValidatorBuilder()->getValidator();

        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank([
                    'message' => 'The name field is required'
                ]),
                new Assert\Length([
                    'min' => 3,
                    'max' => 255,
                    'minMessage' => 'The name must be at least {{ limit }} characters long',
                    'maxMessage' => 'The name cannot be longer than {{ limit }} characters'
                ])
            ],
            'description' => [
                new Assert\NotBlank([
                    'message' => 'The description field is required'
                ]),
                new Assert\Length([
                    'min' => 5,
                    'max' => 1000,
                    'minMessage' => 'The description must be at least {{ limit }} characters long',
                    'maxMessage' => 'The description cannot be longer than {{ limit }} characters'
                ])
            ],
            'annualCharges' => [
                new Assert\NotBlank([
                    'message' => 'The annual charges field is required'
                ]),
                new Assert\Type([
                    'type' => 'numeric',
                    'message' => 'The annual charges must be a number'
                ]),
                new Assert\GreaterThanOrEqual([
                    'value' => 0,
                    'message' => 'The annual charges must be greater than or equal to {{ compared_value }}'
                ])
            ]
        ]);

        $violations = $validator->validate([
            'name' => $this->name,
            'description' => $this->description,
            'annualCharges' => $this->annualCharges
        ], $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                $errors[$propertyPath] = $violation->getMessage();
            }

            throw new ValidationException($errors);
        }
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAnnualCharges(): ?float
    {
        return $this->annualCharges;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'annualCharges' => $this->annualCharges,
        ];
    }
}
