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
    public bool $isDeleted;
    public array $photos = array();
    public array $specifications = array();
    private float $priceFloat;
    private float $discountPriceFloat;

    // Get data from returned database row
    public function getProductDataFromRow(array $row): void {
        $this->id = $row['id'];
        $this->name  = $row['name'];
        $this->description = $row['description'];
        $this->priceFloat = $row['price'];
        $this->price = $row['price'];
        $this->category = $row['category'];
        $this->inventoryAmount = $row['quantity'];
        $this->discountPercent = $row['discount_percent'] ?? 0;
        $this->isDeleted = $row['is_deleted'];
        
        $discountPrice = $this->priceFloat - ($this->priceFloat * ($this->discountPercent / 100));
        $this->discountPriceFloat = $discountPrice;
        $this->discountPrice = number_format((float)$discountPrice, 2, '.', '');
    }

    // Get specifications from specification list
    public function getSpecifactions(array $specifications): void {
        foreach ($specifications as $spec) 
        {
            $this->specifications["" . $spec['label'] . ""] = $spec['info'];        
        }
    }

    // Get photos from photo list
    public function getPhotos(array $photos): void {
        $i = 1;
        foreach ($photos as $photo)
        {
            if ($i === 1) {
                $this->photoPath = $photo['photo_path'];
            }
            $this->photos[] = $photo['photo_path'];
            $i++;
        }
    }

    // Get priced multiplied by quantity
    public function getProductTotalPrice(int $quantity): string {
        $totalPrice = $this->discountPrice * $quantity;
        return number_format((float)$totalPrice, 2, '.', '');
    }
}