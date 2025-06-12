<?php

class Invoice 
{
    public string $id;
    public int $amount;
    public string $description;
    public string $creditCardNumber;

    public function __construct(int $amount, string $description, string $creditCardNumber){
        $this->id = uniqid();
        $this->amount = $amount;
        $this->description = $description;
        $this->creditCardNumber = $creditCardNumber;

    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description'=> $this->description,
            'creditCardNumber' => base64_encode($this->creditCardNumber),
            'foo' => 'bar'
        ];
    }

    public function __unserialize(array $data)
    {
        foreach($data as $property => $value ){
            if(property_exists($this, $property)){
                if($property === 'creditCardNumber'){
                    $this->$property = base64_decode($value);
                    continue;
                }
                $this->$property = $value;
            }
        }
    }

}