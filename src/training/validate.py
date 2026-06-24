import torch

def evaluate_focus(model, dataloader, threshold_deg=15, device=None):
    if device is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    model.eval()
    model.to(device)

    threshold = threshold_deg / 90.0

    correct = 0
    total = 0

    with torch.no_grad():
        for x, y in dataloader:
            x, y = x.to(device), y.to(device)
            out = model(x)

            gt = (torch.abs(y[:, :2]) < threshold).all(dim=1)
            pred = (torch.abs(out[:, :2]) < threshold).all(dim=1)

            correct += (gt == pred).sum().item()
            total += y.size(0)

    print(f" Focus Accuracy: {100 * correct / total:.2f}%")
