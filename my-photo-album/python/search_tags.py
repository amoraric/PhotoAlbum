import sys
from keras.preprocessing.image import load_img
from keras.preprocessing.image import img_to_array
from keras.applications.vgg16 import preprocess_input
from keras.applications.vgg16 import decode_predictions
from keras.applications.vgg16 import VGG16

photo = sys.argv[1]

model = VGG16()

image = load_img(photo, target_size=(224, 224))
image = img_to_array(image)
image = image.reshape((1, image.shape[0], image.shape[1], image.shape[2]))
image = preprocess_input(image)

predicts = model.predict(image)
labels = decode_predictions(predicts)

top_predictions = labels[0][:5]

print(top_predictions)
