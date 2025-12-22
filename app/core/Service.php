<?php
namespace core;

abstract class Service
{
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            
            foreach ($rulesArray as $rule) {
                if ($rule === 'required' && empty($data[$field])) {
                    $errors[$field] = "{$field} is required";
                } elseif ($rule === 'email' && !filter_var($data[$field] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "{$field} must be a valid email";
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($data[$field] ?? '') < $min) {
                        $errors[$field] = "{$field} must be at least {$min} characters";
                    }
                }
            }
        }
        
        return $errors;
    }
}

