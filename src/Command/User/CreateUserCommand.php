<?php

namespace App\Command\User;

class CreateUserCommand
{
    public function __construct(
        public readonly string $username,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?string $position = null,
        public readonly ?string $department = null,
        public readonly ?string $phone = null,
        public readonly ?string $employeeNumber = null,
        public readonly bool $isActive = true,
        public readonly ?int $createdById = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'password' => $this->password,
            'position' => $this->position,
            'department' => $this->department,
            'phone' => $this->phone,
            'employeeNumber' => $this->employeeNumber,
            'isActive' => $this->isActive
        ];
    }
}