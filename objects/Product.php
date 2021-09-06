<?php
class Product {
    
    public int $id;
    
    public string $name;
    
    public ?string $description;
    
    public string $price;
    
    public string $discountPrice;
    
    public string $category;
    
    public int $inventoryAmount;
    
    public int $discountPercent;
    
    public string $photoPath;
    
    public array $specifications = array();
    
    private float $priceFloat;
    
    private float $discountPriceFloat;
    
    public function getProductDataFromRow(array $row): void {
        $this->id = $row['id'];
        $this->name  = $row['name'];
        $this->description = $row['description'];
        $this->priceFloat = $row['price'];
        $this->price = $row['price'];
        $this->category = $row['category'];
        $this->inventoryAmount = $row['quantity'];
        $this->photoPath = $row['photo_path'];
        $this->discountPercent = $row['discount_percent'] ?? 0;
        
        $discountPrice = $this->priceFloat - ($this->priceFloat * ($this->discountPercent / 100));
        $this->discountPriceFloat = $discountPrice;
        $this->discountPrice = number_format((float)$discountPrice, 2, '.', '');
    }
    
    public function getSpecifactions(array $specifications): void {
        foreach ($specifications as $spec) 
        {
            $this->specifications["" . $spec['label'] . ""] = $spec['info'];        
        }
    }
    
    public function getProductTotalPrice(int $quantity): string {
        $totalPrice = $this->discountPrice * $quantity;
        return number_format((float)$totalPrice, 2, '.', '');
    }
}