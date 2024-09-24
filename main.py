from flask import Flask, request, send_file
from PIL import Image, ImageDraw, ImageFont
import os
from io import BytesIO

app = Flask(__name__)

@app.route('/generate-image', methods=['GET'])
def generate_image():
    # Determine the mode (light or dark)
    light_mode = request.args.get('light', 'false') == 'true'

    # Set the background image based on the mode
    if light_mode:
        background_image_path = 'image2.jpg'
    else:
        background_image_path = 'image.jpg'

    # Handle bgid if provided
    bgid = request.args.get('bgid', '')
    if bgid:
        image_url = f'bgs/{bgid}'
        if os.path.exists(image_url):
            background_image_path = image_url

    # Load the background image
    background_image = Image.open(background_image_path)

    # Get the image dimensions
    image_width, image_height = background_image.size

    # Create a blank image with the same dimensions
    image = Image.new('RGB', (image_width, image_height))
    image.paste(background_image)

    # Prepare the text to draw
    text = request.args.get('text', 'No Text Provided')

    # Load the fonts
    font_path = 'font.ttf'  # Path to regular font
    bold_font_path = 'bold-font.ttf'  # Path to bold font
    font_size = 120  # Adjust for better readability

    font = ImageFont.truetype(font_path, font_size)
    bold_font = ImageFont.truetype(bold_font_path, font_size)

    # Set text color based on the mode
    if light_mode:
        text_color = (0, 0, 0)  # Black for light mode
    else:
        text_color = (255, 255, 255)  # White for dark mode

    # Create a drawing context
    draw = ImageDraw.Draw(image)

    # Word-wrap the text to a fixed width
    def wrap_text(text, max_width, font):
        lines = []
        words = text.split()
        current_line = []
        for word in words:
            current_line.append(word)
            width, _ = draw.textsize(' '.join(current_line), font=font)
            if width > max_width:
                current_line.pop()
                lines.append(' '.join(current_line))
                current_line = [word]
        lines.append(' '.join(current_line))
        return lines

    # Split text into styled segments (for *bold* text)
    def get_styled_text(line):
        styled_segments = []
        tokens = line.split('*')
        for i, token in enumerate(tokens):
            if i % 2 == 1:  # Bold text inside * *
                styled_segments.append((token, bold_font))
            else:
                styled_segments.append((token, font))
        return styled_segments

    # Get wrapped lines
    wrapped_lines = wrap_text(text, 800, font)  # Adjust max width as needed

    # Calculate total text block height for vertical centering
    line_height = font.getsize('A')[1]
    text_block_height = len(wrapped_lines) * line_height

    # Calculate starting y-coordinate for vertical centering
    y = (image_height - text_block_height) // 2

    # Draw each line of text
    for line in wrapped_lines:
        x = (image_width - draw.textsize(line, font=font)[0]) // 2  # Center horizontally
        styled_segments = get_styled_text(line)
        for segment, segment_font in styled_segments:
            draw.text((x, y), segment, font=segment_font, fill=text_color)
            x += draw.textsize(segment, font=segment_font)[0]
        y += line_height

    # Save image to an in-memory file
    img_io = BytesIO()
    image.save(img_io, 'PNG')
    img_io.seek(0)

    return send_file(img_io, mimetype='image/png')

if __name__ == '__main__':
    app.run(debug=True)
