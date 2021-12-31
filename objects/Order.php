<?php
class Order
{
    public int $id;
    public string $total;
    private float $totalFloat;
    public string $createdAt;
    public string $name;
    public string $surname;
    public string $email;
    public string $status;
    public string $phoneNr;
    public ?int $userId;
    public int $shippingId;

    // Get only the order data needed for displaying order history from database row
    public function getOrderSummaryFromRow(array $row): void {
        $this->id = $row['id'];
        $this->total = $row['total'];
        $this->totalFloat = $row['total'];
        $this->status = $row['status'];
        $this->createdAt = $row['created_at'];
    }

    // Get all order data from database row
    public function getOrderFromRow(array $row): void {
        $this->id = $row['id'];
        $this->total = $row['total'];
        $this->totalFloat = $row['total'];
        $this->status = $row['status'];
        $this->createdAt = $row['created_at'];
        $this->name = $row['order_name'];
        $this->surname = $row['order_surname'];
        $this->email = $row['order_email'];
        $this->shippingId = $row['shipping_id'];
        $this->userId = $row['user_id'];
        $this->phoneNr = $row['order_phonenr'];
    }

    public function getCreatedAt(): string {
        return date('d.m.Y H:i', strtotime($this->createdAt));
    }

    public function getFullName(): string {
        return $this->name . ' ' . $this->surname;
    }


}