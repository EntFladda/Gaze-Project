/**
 * Canvas Overlay Visualization
 * Drawing utilities for visualization
 */

class CanvasOverlay {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.lineColor = 'rgba(102, 126, 234, 0.8)';
        this.pointColor = 'rgba(76, 175, 80, 0.8)';
    }

    /**
     * Draw box around face
     */
    drawFaceBox(x, y, width, height) {
        this.ctx.strokeStyle = this.lineColor;
        this.ctx.lineWidth = 3;
        this.ctx.strokeRect(x, y, width, height);
    }

    /**
     * Draw points (landmarks)
     */
    drawPoint(x, y, radius = 3) {
        this.ctx.fillStyle = this.pointColor;
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, 2 * Math.PI);
        this.ctx.fill();
    }

    /**
     * Draw line connecting two points
     */
    drawLine(x1, y1, x2, y2, color = null) {
        this.ctx.strokeStyle = color || this.lineColor;
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(x1, y1);
        this.ctx.lineTo(x2, y2);
        this.ctx.stroke();
    }

    /**
     * Draw text
     */
    drawText(text, x, y, size = 14, color = 'white') {
        this.ctx.fillStyle = color;
        this.ctx.font = `bold ${size}px Arial`;
        this.ctx.fillText(text, x, y);
    }

    /**
     * Clear canvas
     */
    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }
}

// Export class for use
const canvasOverlay = new CanvasOverlay(document.getElementById('canvas'));
